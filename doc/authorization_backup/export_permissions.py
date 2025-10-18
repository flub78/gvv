#!/usr/bin/env python3
"""
Export current GVV authorization data to CSV files for backup and analysis
Run before starting the authorization refactoring migration
"""

import mysql.connector
import csv
import json
from datetime import datetime

# Database connection
db_config = {
    'host': 'localhost',
    'user': 'gvv_user',
    'password': 'lfoyfgbj',
    'database': 'gvv2'
}

def export_types_roles():
    """Export types_roles table"""
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM types_roles ORDER BY id")
    rows = cursor.fetchall()

    with open('types_roles_export.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['id', 'nom', 'description'])
        writer.writeheader()
        writer.writerows(rows)

    cursor.close()
    conn.close()
    print(f"✓ Exported {len(rows)} roles to types_roles_export.csv")

def export_permissions():
    """Export permissions table with decoded URI data"""
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT p.*, tr.nom as role_name
        FROM permissions p
        JOIN types_roles tr ON p.role_id = tr.id
        ORDER BY p.role_id
    """)
    rows = cursor.fetchall()

    # Export raw permissions
    with open('permissions_raw_export.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['id', 'role_id', 'role_name', 'data'])
        writer.writeheader()
        writer.writerows(rows)

    # Export parsed permissions (URI list)
    uri_rows = []
    for row in rows:
        # Parse PHP serialized data
        import pickle
        import phpserialize
        try:
            data = phpserialize.loads(row['data'].encode('utf-8'))
            if b'uri' in data:
                uris = data[b'uri']
                for uri in uris.values():
                    uri_rows.append({
                        'role_id': row['role_id'],
                        'role_name': row['role_name'],
                        'uri': uri.decode('utf-8') if isinstance(uri, bytes) else uri
                    })
        except Exception as e:
            print(f"Warning: Could not parse permissions for role {row['role_name']}: {e}")

    with open('permissions_uris_export.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['role_id', 'role_name', 'uri'])
        writer.writeheader()
        writer.writerows(uri_rows)

    cursor.close()
    conn.close()
    print(f"✓ Exported {len(rows)} permission records to permissions_raw_export.csv")
    print(f"✓ Exported {len(uri_rows)} URIs to permissions_uris_export.csv")

def export_user_roles():
    """Export user_roles_per_section assignments"""
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT urps.id, urps.user_id, u.username, urps.types_roles_id,
               tr.nom as role_name, urps.section_id, s.nom as section_name
        FROM user_roles_per_section urps
        JOIN users u ON urps.user_id = u.id
        JOIN types_roles tr ON urps.types_roles_id = tr.id
        JOIN sections s ON urps.section_id = s.id
        ORDER BY u.username, s.nom
    """)
    rows = cursor.fetchall()

    with open('user_roles_per_section_export.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['id', 'user_id', 'username', 'types_roles_id',
                                                 'role_name', 'section_id', 'section_name'])
        writer.writeheader()
        writer.writerows(rows)

    cursor.close()
    conn.close()
    print(f"✓ Exported {len(rows)} user role assignments to user_roles_per_section_export.csv")

def export_roles_hierarchy():
    """Export roles table (hierarchy)"""
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM roles ORDER BY id")
    rows = cursor.fetchall()

    with open('roles_hierarchy_export.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=['id', 'parent_id', 'name'])
        writer.writeheader()
        writer.writerows(rows)

    cursor.close()
    conn.close()
    print(f"✓ Exported {len(rows)} role hierarchy records to roles_hierarchy_export.csv")

def generate_summary():
    """Generate summary statistics"""
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    summary = []
    summary.append("=" * 60)
    summary.append("GVV Authorization System - Current State Summary")
    summary.append(f"Export Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    summary.append("=" * 60)
    summary.append("")

    # Count roles
    cursor.execute("SELECT COUNT(*) as count FROM types_roles")
    role_count = cursor.fetchone()['count']
    summary.append(f"Total Roles: {role_count}")

    # Count users per role
    cursor.execute("""
        SELECT tr.nom as role_name, COUNT(DISTINCT urps.user_id) as user_count
        FROM types_roles tr
        LEFT JOIN user_roles_per_section urps ON tr.id = urps.types_roles_id
        GROUP BY tr.id, tr.nom
        ORDER BY tr.id
    """)
    summary.append("\nUsers per Role:")
    for row in cursor.fetchall():
        summary.append(f"  - {row['role_name']}: {row['user_count']} users")

    # Count sections
    cursor.execute("SELECT COUNT(*) as count FROM sections")
    section_count = cursor.fetchone()['count']
    summary.append(f"\nTotal Sections: {section_count}")

    cursor.close()
    conn.close()

    summary_text = '\n'.join(summary)
    with open('authorization_export_summary.txt', 'w', encoding='utf-8') as f:
        f.write(summary_text)

    print("\n" + summary_text)

if __name__ == '__main__':
    try:
        print("Starting authorization data export...")
        print()
        export_types_roles()
        export_permissions()
        export_user_roles()
        export_roles_hierarchy()
        generate_summary()
        print()
        print("✓ All exports completed successfully!")
        print(f"  Files saved in: {__file__.rsplit('/', 1)[0]}/")
    except Exception as e:
        print(f"✗ Error during export: {e}")
        import traceback
        traceback.print_exc()
