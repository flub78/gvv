#!/usr/bin/env python3
"""
Remplit un formulaire PDF avec des données et ajoute optionnellement des images
(signatures, tampons, etc.) à des positions spécifiées.

Usage:
    pdf_fill_form.py <template.pdf> <output.pdf> <data.json>

Le fichier JSON contient:
{
    "fields": {
        "Nom de famille": "DUPONT",
        "Prénom": "Jean",
        ...
    },
    "images": [
        {
            "file": "signature.png",
            "page": 0,
            "x": 200,
            "y": 100,
            "width": 150,
            "height": 50
        }
    ]
}

Coordonnées: origine en bas à gauche, unités en points PDF (1 point = 1/72 pouce)

Exemple:
    ./bin/pdf_fill_form.py template.pdf output.pdf data.json
"""

import sys
import json
import subprocess
import tempfile
import os
from pathlib import Path

try:
    from PyPDF2 import PdfReader, PdfWriter
except ImportError:
    print("Erreur: PyPDF2 n'est pas installé.", file=sys.stderr)
    sys.exit(1)

try:
    from PIL import Image
except ImportError:
    print("Erreur: Pillow n'est pas installé.", file=sys.stderr)
    sys.exit(1)


def create_image_overlay_pdf(image_path, page_width, page_height, x, y, width, height, output_pdf):
    """
    Crée un PDF transparent avec une image positionnée.
    Utilise ImageMagick pour la conversion.

    Args:
        image_path: Chemin vers l'image (PNG, JPG, etc.)
        page_width: Largeur de la page en points
        page_height: Hauteur de la page en points
        x: Position X de l'image (depuis la gauche)
        y: Position Y de l'image (depuis le bas)
        width: Largeur de l'image en points
        height: Hauteur de l'image en points
        output_pdf: Chemin du PDF de sortie
    """
    with tempfile.TemporaryDirectory() as tmpdir:
        # Redimensionner l'image à la taille voulue
        img = Image.open(image_path)

        # Convertir en RGBA si nécessaire pour gérer la transparence
        if img.mode != 'RGBA':
            img = img.convert('RGBA')

        # Calculer la taille en pixels (72 dpi)
        dpi = 72
        width_px = int(width * dpi / 72)
        height_px = int(height * dpi / 72)

        img_resized = img.resize((width_px, height_px), Image.Resampling.LANCZOS)

        # Créer une image de la taille de la page avec fond transparent
        page_width_px = int(page_width)
        page_height_px = int(page_height)

        page_img = Image.new('RGBA', (page_width_px, page_height_px), (255, 255, 255, 0))

        # Positionner l'image (y est depuis le bas en PDF, mais depuis le haut en PIL)
        paste_x = int(x)
        paste_y = int(page_height - y - height)  # Conversion coordonnées PDF -> PIL

        page_img.paste(img_resized, (paste_x, paste_y), img_resized)

        # Sauvegarder en PNG temporaire
        temp_png = os.path.join(tmpdir, 'overlay.png')
        page_img.save(temp_png, 'PNG')

        # Convertir en PDF avec ImageMagick
        cmd = [
            'convert',
            temp_png,
            '-background', 'none',
            '-page', f'{page_width_px}x{page_height_px}+0+0',
            output_pdf
        ]

        result = subprocess.run(cmd, capture_output=True, text=True)
        if result.returncode != 0:
            raise RuntimeError(f"Erreur ImageMagick: {result.stderr}")


def fill_pdf(template_path, output_path, data):
    """
    Remplit un formulaire PDF avec des données et des images.

    Args:
        template_path: Chemin vers le PDF template
        output_path: Chemin du PDF de sortie
        data: Dictionnaire avec 'fields' et optionnellement 'images'
    """
    reader = PdfReader(template_path)
    writer = PdfWriter()

    # Copier toutes les pages
    for page in reader.pages:
        writer.add_page(page)

    # Remplir les champs de formulaire
    fields = data.get('fields', {})
    if fields:
        writer.update_page_form_field_values(writer.pages[0], fields)

    # Ajouter les images (signatures, etc.)
    images = data.get('images', [])

    if images:
        with tempfile.TemporaryDirectory() as tmpdir:
            for i, img_spec in enumerate(images):
                img_file = img_spec.get('file')
                if not img_file or not os.path.exists(img_file):
                    print(f"Attention: Image '{img_file}' non trouvée, ignorée.", file=sys.stderr)
                    continue

                page_num = img_spec.get('page', 0)
                if page_num >= len(writer.pages):
                    print(f"Attention: Page {page_num} inexistante, image ignorée.", file=sys.stderr)
                    continue

                # Obtenir les dimensions de la page
                page = writer.pages[page_num]
                page_width = float(page.mediabox.width)
                page_height = float(page.mediabox.height)

                x = img_spec.get('x', 0)
                y = img_spec.get('y', 0)
                width = img_spec.get('width', 100)
                height = img_spec.get('height', 50)

                # Créer le PDF overlay
                overlay_pdf = os.path.join(tmpdir, f'overlay_{i}.pdf')

                try:
                    create_image_overlay_pdf(
                        img_file, page_width, page_height,
                        x, y, width, height, overlay_pdf
                    )

                    # Fusionner avec la page
                    overlay_reader = PdfReader(overlay_pdf)
                    if len(overlay_reader.pages) > 0:
                        page.merge_page(overlay_reader.pages[0])
                        print(f"Image '{img_file}' ajoutée à la page {page_num}.")

                except Exception as e:
                    print(f"Erreur lors de l'ajout de l'image '{img_file}': {e}", file=sys.stderr)

    # Écrire le PDF final
    with open(output_path, 'wb') as f:
        writer.write(f)

    print(f"PDF généré: {output_path}")


def main():
    args = sys.argv[1:]

    if len(args) < 3 or '-h' in args or '--help' in args:
        print(__doc__)
        sys.exit(0 if '-h' in args or '--help' in args else 1)

    template_path = args[0]
    output_path = args[1]
    data_path = args[2]

    # Vérifications
    if not os.path.exists(template_path):
        print(f"Erreur: Template '{template_path}' non trouvé.", file=sys.stderr)
        sys.exit(1)

    if not os.path.exists(data_path):
        print(f"Erreur: Fichier de données '{data_path}' non trouvé.", file=sys.stderr)
        sys.exit(1)

    # Charger les données
    try:
        with open(data_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except json.JSONDecodeError as e:
        print(f"Erreur: JSON invalide: {e}", file=sys.stderr)
        sys.exit(1)

    # Remplir le PDF
    try:
        fill_pdf(template_path, output_path, data)
    except Exception as e:
        print(f"Erreur: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()
