#!/usr/bin/env python3
"""
Extrait les champs de saisie d'un formulaire PDF (AcroForm).

Usage:
    pdf_extract_fields.py <fichier.pdf> [--json] [--verbose]

Options:
    --json      Sortie au format JSON
    --verbose   Affiche les détails de chaque champ

Exemple:
    ./bin/pdf_extract_fields.py doc/design_notes/documents/134iFormlic.pdf
    ./bin/pdf_extract_fields.py doc/design_notes/documents/134iFormlic.pdf --json
"""

import sys
import json
from pathlib import Path

try:
    from PyPDF2 import PdfReader
except ImportError:
    print("Erreur: PyPDF2 n'est pas installé.", file=sys.stderr)
    print("Installer avec: apt install python3-pypdf2", file=sys.stderr)
    sys.exit(1)


# Mapping des types de champs PDF
FIELD_TYPES = {
    '/Tx': 'text',
    '/Btn': 'checkbox',
    '/Ch': 'choice',
    '/Sig': 'signature',
}


def extract_fields(pdf_path):
    """
    Extrait les champs d'un formulaire PDF.

    Args:
        pdf_path: Chemin vers le fichier PDF

    Returns:
        Liste de dictionnaires contenant les informations des champs
    """
    reader = PdfReader(pdf_path)
    fields = reader.get_fields()

    if not fields:
        return []

    result = []
    for name, field in fields.items():
        field_type_raw = field.get('/FT', '')
        field_type = FIELD_TYPES.get(field_type_raw, field_type_raw)

        field_info = {
            'name': name,
            'type': field_type,
            'type_raw': field_type_raw,
        }

        # Valeur par défaut
        value = field.get('/V')
        if value:
            field_info['value'] = str(value)

        # Valeur par défaut alternative
        dv = field.get('/DV')
        if dv:
            field_info['default_value'] = str(dv)

        # Options pour les listes déroulantes
        options = field.get('/Opt')
        if options:
            field_info['options'] = [str(opt) for opt in options]

        # Champ requis
        ff = field.get('/Ff', 0)
        if isinstance(ff, int):
            field_info['required'] = bool(ff & 2)
            field_info['read_only'] = bool(ff & 1)

        # Longueur max pour les champs texte
        max_len = field.get('/MaxLen')
        if max_len:
            field_info['max_length'] = int(max_len)

        result.append(field_info)

    return result


def print_table(fields):
    """Affiche les champs sous forme de tableau."""
    if not fields:
        print("Aucun champ de formulaire trouvé.")
        return

    # Calcul des largeurs de colonnes
    name_width = max(len(f['name']) for f in fields)
    name_width = max(name_width, 4)  # minimum "Name"

    # En-tête
    print(f"{'Champ':<{name_width}} | {'Type':<10} | {'Valeur'}")
    print(f"{'-' * name_width}-+-{'-' * 10}-+-{'-' * 20}")

    # Lignes
    for field in fields:
        name = field['name']
        ftype = field['type']
        value = field.get('value', '')
        print(f"{name:<{name_width}} | {ftype:<10} | {value}")


def print_verbose(fields):
    """Affiche les champs avec tous les détails."""
    if not fields:
        print("Aucun champ de formulaire trouvé.")
        return

    for i, field in enumerate(fields, 1):
        print(f"\n=== Champ {i}: {field['name']} ===")
        print(f"  Type: {field['type']} ({field['type_raw']})")

        if 'value' in field:
            print(f"  Valeur: {field['value']}")
        if 'default_value' in field:
            print(f"  Valeur par défaut: {field['default_value']}")
        if 'options' in field:
            print(f"  Options: {', '.join(field['options'])}")
        if 'max_length' in field:
            print(f"  Longueur max: {field['max_length']}")
        if field.get('required'):
            print("  Requis: Oui")
        if field.get('read_only'):
            print("  Lecture seule: Oui")


def main():
    # Parsing des arguments
    args = sys.argv[1:]

    if not args or '-h' in args or '--help' in args:
        print(__doc__)
        sys.exit(0)

    pdf_path = None
    output_json = False
    verbose = False

    for arg in args:
        if arg == '--json':
            output_json = True
        elif arg == '--verbose':
            verbose = True
        elif not arg.startswith('-'):
            pdf_path = arg

    if not pdf_path:
        print("Erreur: Veuillez spécifier un fichier PDF.", file=sys.stderr)
        sys.exit(1)

    # Vérification du fichier
    path = Path(pdf_path)
    if not path.exists():
        print(f"Erreur: Le fichier '{pdf_path}' n'existe pas.", file=sys.stderr)
        sys.exit(1)

    if not path.suffix.lower() == '.pdf':
        print(f"Attention: Le fichier ne semble pas être un PDF.", file=sys.stderr)

    # Extraction des champs
    try:
        fields = extract_fields(pdf_path)
    except Exception as e:
        print(f"Erreur lors de la lecture du PDF: {e}", file=sys.stderr)
        sys.exit(1)

    # Affichage du résultat
    if output_json:
        print(json.dumps(fields, indent=2, ensure_ascii=False))
    elif verbose:
        print(f"Fichier: {pdf_path}")
        print(f"Nombre de champs: {len(fields)}")
        print_verbose(fields)
    else:
        print(f"Fichier: {pdf_path}")
        print(f"Nombre de champs: {len(fields)}\n")
        print_table(fields)


if __name__ == '__main__':
    main()
