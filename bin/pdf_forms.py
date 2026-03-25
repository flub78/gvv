#!/usr/bin/env python3
"""
Outils PDF forms (AcroForm).

Usage:
    pdf_forms.py extract --pdf file.pdf [--json_fields fields.json]
    pdf_forms.py fill --pdf file.pdf --json_data data.json [--json_fields fields.json] [--output output.pdf]

Commandes:
    extract   Extrait les champs AcroForm en JSON (stdout et/ou fichier)
    fill      Remplit un PDF avec des données JSON

Notes:
    - Distinction radio/checkbox via le flag /Ff bit 16 (1-indexed).
    - Les accents UTF-8 sont préservés (ensure_ascii=False + NeedAppearances=true).
"""

import argparse
import json
import os
import sys
import tempfile
import textwrap
from pathlib import Path

try:
    from PyPDF2 import PdfReader, PdfWriter, Transformation
    from PyPDF2.generic import BooleanObject, NameObject
except ImportError:
    print("Erreur: PyPDF2 n'est pas installé. Installer avec: apt install python3-pypdf2", file=sys.stderr)
    sys.exit(1)


FIELD_TYPES = {
    '/Tx': 'text',
    '/Ch': 'choice',
    '/Sig': 'signature',
}


def _normalize_pdf_value(value):
    if value is None:
        return None
    if isinstance(value, bytes):
        try:
            return value.decode('utf-8')
        except UnicodeDecodeError:
            return value.decode('latin-1', errors='replace')
    return str(value)


def _button_type(field):
    ff = field.get('/Ff', 0)
    try:
        ff = int(ff)
    except Exception:
        ff = 0

    # Bit 16 (1-indexed) => masque 1 << 15
    is_radio = bool(ff & (1 << 15))
    return 'radio' if is_radio else 'checkbox'


def extract_fields(pdf_path):
    reader = PdfReader(pdf_path)
    fields = reader.get_fields() or {}

    extracted = []
    for name, field in fields.items():
        field_type_raw = field.get('/FT', '')
        if field_type_raw == '/Btn':
            field_type = _button_type(field)
        else:
            field_type = FIELD_TYPES.get(field_type_raw, str(field_type_raw))

        item = {
            'name': name,
            'type': field_type,
            'type_raw': str(field_type_raw),
        }

        value = _normalize_pdf_value(field.get('/V'))
        if value is not None:
            item['value'] = value

        default_value = _normalize_pdf_value(field.get('/DV'))
        if default_value is not None:
            item['default_value'] = default_value

        options = field.get('/Opt')
        if options:
            clean_opts = []
            for opt in options:
                if isinstance(opt, (list, tuple)) and len(opt) >= 2:
                    clean_opts.append(_normalize_pdf_value(opt[1]))
                else:
                    clean_opts.append(_normalize_pdf_value(opt))
            item['options'] = clean_opts

        ff = field.get('/Ff', 0)
        try:
            ff = int(ff)
        except Exception:
            ff = 0
        item['required'] = bool(ff & 2)
        item['read_only'] = bool(ff & 1)

        max_len = field.get('/MaxLen')
        if max_len is not None:
            try:
                item['max_length'] = int(max_len)
            except Exception:
                pass

        extracted.append(item)

    return extracted


def _parse_json_file(path):
    p = Path(path)
    if not p.exists() or not p.is_file():
        raise ValueError(f"json introuvable: {path}")
    try:
        text = p.read_text(encoding='utf-8')
        return json.loads(text)
    except Exception as exc:
        raise ValueError(f"json invalide ({path}): {exc}")


def _extract_fill_payload(json_data):
    if isinstance(json_data, dict) and 'fields' in json_data:
        fields_data = json_data['fields']
        images_data = json_data.get('images', [])
    else:
        fields_data = json_data
        images_data = []

    if not isinstance(fields_data, dict):
        raise ValueError("json_data doit contenir un objet JSON ou {'fields': {...}}")
    if not isinstance(images_data, list):
        raise ValueError("json_data.images doit être une liste")

    return fields_data, images_data


def _create_image_pdf(image_path, output_pdf):
    try:
        from PIL import Image
    except ImportError as exc:
        raise RuntimeError("Pillow est requis pour l'insertion d'images (apt install python3-pil)") from exc

    with Image.open(image_path) as img:
        # Convert to RGB because PDF export in Pillow does not support alpha channel directly.
        if img.mode != 'RGB':
            img = img.convert('RGB')
        img.save(output_pdf, 'PDF', resolution=72.0)


def _resolve_overlay_pdf(spec, idx, tmpdir):
    """
    Resolve one image spec into a PDF path ready to merge.
    Supports either:
    - {"pdf": "stamp.pdf", ...} -> no extra dependency
    - {"file": "signature.png|jpg", ...} -> requires Pillow
    """
    overlay_pdf_path = spec.get('pdf')
    if overlay_pdf_path:
        if not os.path.exists(overlay_pdf_path):
            raise ValueError(f"images[{idx}].pdf introuvable: {overlay_pdf_path}")
        return overlay_pdf_path

    image_path = spec.get('file')
    if not image_path:
        raise ValueError(f"images[{idx}] doit contenir 'file' (PNG/JPEG) ou 'pdf' (overlay PDF)")
    if not os.path.exists(image_path):
        raise ValueError(f"images[{idx}].file introuvable: {image_path}")

    overlay_pdf = os.path.join(tmpdir, f'image_overlay_{idx}.pdf')
    _create_image_pdf(image_path, overlay_pdf)
    return overlay_pdf


def _merge_images(writer, images_data):
    if not images_data:
        return

    with tempfile.TemporaryDirectory() as tmpdir:
        for idx, spec in enumerate(images_data):
            if not isinstance(spec, dict):
                raise ValueError(f"images[{idx}] doit être un objet")

            try:
                page_num = int(spec.get('page', 0))
            except Exception as exc:
                raise ValueError(f"images[{idx}].page doit être un entier") from exc

            if page_num < 0 or page_num >= len(writer.pages):
                raise ValueError(f"images[{idx}].page hors limites: {page_num}")

            try:
                x = float(spec.get('x', 0))
                y = float(spec.get('y', 0))
                width = float(spec.get('width', 100))
                height = float(spec.get('height', 50))
            except Exception as exc:
                raise ValueError(f"images[{idx}] coordonnées invalides (x/y/width/height)") from exc

            if width <= 0 or height <= 0:
                raise ValueError(f"images[{idx}] width/height doivent être > 0")

            overlay_pdf = _resolve_overlay_pdf(spec, idx, tmpdir)

            overlay_reader = PdfReader(overlay_pdf)
            if not overlay_reader.pages:
                raise ValueError(f"images[{idx}] impossible de charger l'image en PDF")

            overlay_page = overlay_reader.pages[0]
            src_w = float(overlay_page.mediabox.width)
            src_h = float(overlay_page.mediabox.height)

            if src_w <= 0 or src_h <= 0:
                raise ValueError(f"images[{idx}] dimensions source invalides")

            transform = Transformation().scale(width / src_w, height / src_h).translate(x, y)
            target_page = writer.pages[page_num]
            if hasattr(target_page, 'merge_transformed_page'):
                target_page.merge_transformed_page(overlay_page, transform)
            elif hasattr(target_page, 'mergeTransformedPage'):
                target_page.mergeTransformedPage(overlay_page, transform)
            else:
                raise RuntimeError("Version de PyPDF2 incompatible: merge_transformed_page indisponible")


def _set_need_appearances(writer, reader):
    root_ref = reader.trailer.get('/Root')
    if root_ref is None:
        return

    root = root_ref.get_object()
    if '/AcroForm' not in root:
        return

    acro = root['/AcroForm']
    writer._root_object.update({NameObject('/AcroForm'): acro})
    try:
        acro_obj = writer._root_object['/AcroForm'].get_object()
        acro_obj.update({NameObject('/NeedAppearances'): BooleanObject(True)})
    except Exception:
        pass


def _coerce_fill_value(value, field_info):
    ftype = field_info.get('type', '')

    if ftype == 'checkbox':
        if isinstance(value, bool):
            return value
        if isinstance(value, str):
            low = value.strip().lower()
            if low in ('1', 'true', 'yes', 'on', '/yes'):
                return True
            if low in ('0', 'false', 'no', 'off', '/off'):
                return False
        if isinstance(value, (int, float)):
            return bool(value)
    return _normalize_pdf_value(value)


def _get_checkbox_on_states(reader):
    """
    Build a map field_name -> on_state_name (e.g. /On, /Yes) from widget AP/N.
    """
    on_states = {}
    for page in reader.pages:
        annots_ref = page.get('/Annots')
        if not annots_ref:
            continue
        annots = annots_ref.get_object() if hasattr(annots_ref, 'get_object') else annots_ref
        for annot_ref in annots:
            annot = annot_ref.get_object()
            if annot.get('/FT') != '/Btn':
                continue
            name = annot.get('/T')
            if not name:
                continue

            # Skip radio button groups
            ff = annot.get('/Ff', 0)
            try:
                ff = int(ff)
            except Exception:
                ff = 0
            is_radio = bool(ff & (1 << 15))
            if is_radio:
                continue

            ap = annot.get('/AP')
            if not ap or '/N' not in ap:
                continue

            n = ap['/N']
            nobj = n.get_object() if hasattr(n, 'get_object') else n
            if not hasattr(nobj, 'keys'):
                continue

            keys = [str(k) for k in nobj.keys()]
            for key in keys:
                if key != '/Off':
                    on_states[str(name)] = key
                    break

    return on_states


def _apply_checkbox_widget_states(writer, checkbox_values):
    """
    Ensure widget appearance (/AS) and value (/V) are aligned for checkboxes.
    """
    if not checkbox_values:
        return

    for page in writer.pages:
        annots_ref = page.get('/Annots')
        if not annots_ref:
            continue
        annots = annots_ref.get_object() if hasattr(annots_ref, 'get_object') else annots_ref
        for annot_ref in annots:
            annot = annot_ref.get_object()
            if annot.get('/FT') != '/Btn':
                continue
            field_name = str(annot.get('/T') or '')
            if field_name not in checkbox_values:
                continue

            state = checkbox_values[field_name]
            annot.update({NameObject('/AS'): NameObject(state)})
            annot.update({NameObject('/V'): NameObject(state)})


def fill_pdf(pdf_path, output_path, json_data):
    reader = PdfReader(pdf_path)
    writer = PdfWriter()
    checkbox_on_states = _get_checkbox_on_states(reader)

    for page in reader.pages:
        writer.add_page(page)

    extracted = extract_fields(pdf_path)
    known_fields = {f['name']: f for f in extracted}

    fields_data, images_data = _extract_fill_payload(json_data)

    unknown_fields = [k for k in fields_data.keys() if k not in known_fields]
    if unknown_fields:
        raise ValueError('champs inconnus: ' + ', '.join(sorted(unknown_fields)))

    prepared_values = {}
    checkbox_values = {}
    for field_name, value in fields_data.items():
        coerced = _coerce_fill_value(value, known_fields[field_name])
        if known_fields[field_name].get('type') == 'checkbox':
            on_state = checkbox_on_states.get(field_name, '/Yes')
            state = on_state if bool(coerced) else '/Off'
            prepared_values[field_name] = state
            checkbox_values[field_name] = state
        else:
            prepared_values[field_name] = coerced

    if prepared_values:
        for page in writer.pages:
            writer.update_page_form_field_values(page, prepared_values)

    _apply_checkbox_widget_states(writer, checkbox_values)

    _merge_images(writer, images_data)

    _set_need_appearances(writer, reader)

    with open(output_path, 'wb') as out:
        writer.write(out)


def cmd_extract(args):
    path = Path(args.pdf)
    if not path.exists():
        raise FileNotFoundError(f"fichier introuvable: {args.pdf}")

    fields = extract_fields(str(path))
    payload = json.dumps(fields, indent=2, ensure_ascii=False)

    if args.json_fields:
        out = Path(args.json_fields)
        if out.parent and not out.parent.exists():
            out.parent.mkdir(parents=True, exist_ok=True)
        out.write_text(payload + '\n', encoding='utf-8')
        return

    print(payload)


def cmd_fill(args):
    src = Path(args.pdf)
    if not src.exists():
        raise FileNotFoundError(f"fichier introuvable: {args.pdf}")

    if not args.json_data:
        raise ValueError("--json_data est obligatoire")

    payload = _parse_json_file(args.json_data)

    if args.output:
        out = Path(args.output)
    else:
        out = Path.cwd() / (src.stem + '.filled.pdf')

    if out.parent and not out.parent.exists():
        out.parent.mkdir(parents=True, exist_ok=True)

    if args.json_fields:
        ref = Path(args.json_fields)
        if ref.exists():
            known = _parse_json_file(args.json_fields)
            if not isinstance(known, list):
                raise ValueError("--json_fields doit contenir une liste de champs")
            known_names = set([f.get('name') for f in known if isinstance(f, dict) and f.get('name')])
            if isinstance(payload, dict) and 'fields' in payload and isinstance(payload['fields'], dict):
                unknown = sorted([k for k in payload['fields'].keys() if k not in known_names])
                if unknown:
                    raise ValueError('champs inconnus (json_fields): ' + ', '.join(unknown))
            elif isinstance(payload, dict):
                unknown = sorted([k for k in payload.keys() if k not in known_names])
                if unknown:
                    raise ValueError('champs inconnus (json_fields): ' + ', '.join(unknown))
        else:
            extracted = extract_fields(str(src))
            ref_payload = json.dumps(extracted, indent=2, ensure_ascii=False)
            if ref.parent and not ref.parent.exists():
                ref.parent.mkdir(parents=True, exist_ok=True)
            ref.write_text(ref_payload + '\n', encoding='utf-8')

    fill_pdf(str(src), str(out), payload)
    return


def build_parser():
    parser = argparse.ArgumentParser(
        description='Extraction et remplissage de formulaires PDF (AcroForm).',
        epilog=textwrap.dedent(
            """
            Exemples:
              python3 bin/pdf_forms.py extract --pdf file.pdf --json_fields doc/prds/reference/fields.json
              python3 bin/pdf_forms.py fill --pdf file.pdf --json_data doc/prds/reference/data.json --json_fields doc/prds/reference/fields.json --output /tmp/filled.pdf
            """
        ),
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    sub = parser.add_subparsers(dest='command', required=True)

    p_extract = sub.add_parser(
        'extract',
        help='Extrait les champs AcroForm en JSON',
        description='Extrait la liste des champs (nom, type, valeur par défaut, options) depuis un PDF AcroForm.',
        epilog='Exemple: python3 bin/pdf_forms.py extract --pdf form.pdf --json_fields fields.json\nSi --json_fields est fourni, stdout reste vide (mode exclusif).',
    )
    p_extract.add_argument('--pdf', required=True, metavar='FILE', help='Chemin du PDF source (AcroForm)')
    p_extract.add_argument('--json_fields', metavar='FILE', help='Fichier JSON de sortie des champs (optionnel)')
    p_extract.set_defaults(func=cmd_extract)

    p_fill = sub.add_parser(
        'fill',
        help='Remplit un PDF à partir de données JSON',
        description='Remplit les champs d\'un PDF AcroForm à partir d\'un fichier JSON.',
        epilog=textwrap.dedent(
            """
            Format attendu pour --json_data:
              {
                "fields": {
                  "Nom de famille 1": "ELEVE TEST",
                  "Prénoms": "Andre"
                                },
                                "images": [
                                    {
                                        "file": "signature.png",
                                        "page": 0,
                                        "x": 200,
                                        "y": 100,
                                        "width": 150,
                                        "height": 50
                                    },
                                    {
                                        "pdf": "signature_overlay.pdf",
                                        "page": 0,
                                        "x": 200,
                                        "y": 100,
                                        "width": 150,
                                        "height": 50
                                    }
                                ]
              }

            Exemple:
              python3 bin/pdf_forms.py fill --pdf form.pdf --json_data data.json --output form.filled.pdf

                        Si --output est utilisé (ou la sortie par défaut), stdout reste vide (mode exclusif).
            """
        ),
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    p_fill.add_argument('--pdf', required=True, metavar='FILE', help='Chemin du PDF source (AcroForm)')
    p_fill.add_argument('--json_data', required=True, metavar='FILE', help='Fichier JSON des données à injecter')
    p_fill.add_argument('--json_fields', metavar='FILE', help='Fichier JSON de référence des champs (validation, optionnel)')
    p_fill.add_argument('--output', metavar='FILE', help='Chemin du PDF de sortie (défaut: <pdf>.filled.pdf)')
    p_fill.set_defaults(func=cmd_fill)

    return parser


def main():
    parser = build_parser()
    args = parser.parse_args()

    try:
        args.func(args)
    except Exception as exc:
        print(f"Erreur: {exc}", file=sys.stderr)
        return 1

    return 0


if __name__ == '__main__':
    sys.exit(main())
