#!/usr/bin/env python3
"""
Test migration 049 with rollback capability

This script runs the migration by executing the PHP migration class
and provides rollback capability if something goes wrong.

Usage: python3 run_migration_test.py
"""

import pymysql
import sys
import subprocess

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'gvv_user',
    'password': 'lfoyfgbj',
    'database': 'gvv2',
    'charset': 'utf8mb4'
}

def check_tables(cursor):
    """Check which email_lists tables exist"""
    tables = ['email_lists', 'email_list_roles', 'email_list_members', 'email_list_external']
    cursor.execute("""
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = %s
        AND table_name IN %s
    """, (DB_CONFIG['database'], tables))

    existing = [row[0] for row in cursor.fetchall()]
    return {table: table in existing for table in tables}

def check_triggers(cursor):
    """Check which triggers exist"""
    triggers = [
        'email_lists_created_at',
        'email_lists_updated_at',
        'email_list_roles_granted_at',
        'email_list_members_added_at',
        'email_list_external_added_at'
    ]
    cursor.execute("""
        SELECT trigger_name
        FROM information_schema.triggers
        WHERE trigger_schema = %s
        AND trigger_name IN %s
    """, (DB_CONFIG['database'], triggers))

    existing = [row[0] for row in cursor.fetchall()]
    return {trigger: trigger in existing for trigger in triggers}

def run_migration_down(cursor):
    """Run the down() method to rollback"""
    print("\n=== Running Migration DOWN (rollback) ===\n")

    # Drop triggers
    triggers = [
        'email_list_external_added_at',
        'email_list_members_added_at',
        'email_list_roles_granted_at',
        'email_lists_updated_at',
        'email_lists_created_at'
    ]

    for trigger in triggers:
        try:
            cursor.execute(f"DROP TRIGGER IF EXISTS {trigger}")
            print(f"✓ Dropped trigger: {trigger}")
        except Exception as e:
            print(f"⚠ Warning dropping trigger {trigger}: {e}")

    # Drop tables
    tables = ['email_list_external', 'email_list_members', 'email_list_roles', 'email_lists']

    for table in tables:
        try:
            cursor.execute(f"DROP TABLE IF EXISTS {table}")
            print(f"✓ Dropped table: {table}")
        except Exception as e:
            print(f"⚠ Warning dropping table {table}: {e}")

    print("\n✓ Rollback completed")

def main():
    print("\n=== Migration 049 Test with Rollback ===\n")

    try:
        # Connect to database
        print("Connecting to database...")
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("✓ Connected\n")

        # Check initial state
        print("Checking initial state...")
        initial_tables = check_tables(cursor)
        initial_triggers = check_triggers(cursor)

        print("\nExisting tables:")
        for table, exists in initial_tables.items():
            print(f"  - {table}: {'EXISTS' if exists else 'NOT FOUND'}")

        print("\nExisting triggers:")
        for trigger, exists in initial_triggers.items():
            print(f"  - {trigger}: {'EXISTS' if exists else 'NOT FOUND'}")

        # Ask if we should proceed
        print("\n" + "="*60)
        response = input("\nProceed with running migration UP? (y/N): ").strip().lower()

        if response != 'y':
            print("Aborted by user")
            return 0

        # Run migration via PHP CLI
        print("\n=== Running Migration UP via PHP ===\n")
        result = subprocess.run(
            ['php', 'index.php', 'migrate', 'version', '49'],
            capture_output=True,
            text=True,
            timeout=60
        )

        print("STDOUT:")
        print(result.stdout)

        if result.stderr:
            print("\nSTDERR:")
            print(result.stderr)

        if result.returncode != 0:
            print(f"\n✗ Migration failed with exit code {result.returncode}")

            # Check what changed
            final_tables = check_tables(cursor)
            final_triggers = check_triggers(cursor)

            changed = False
            for table in initial_tables:
                if initial_tables[table] != final_tables[table]:
                    changed = True
                    break

            if changed:
                response = input("\nDo you want to rollback changes? (Y/n): ").strip().lower()
                if response != 'n':
                    run_migration_down(cursor)
                    conn.commit()

            return 1

        # Check final state
        print("\n=== Verifying Migration Results ===\n")
        final_tables = check_tables(cursor)
        final_triggers = check_triggers(cursor)

        print("Tables after migration:")
        for table, exists in final_tables.items():
            status = "✓ EXISTS" if exists else "✗ NOT FOUND"
            print(f"  - {table}: {status}")

        print("\nTriggers after migration:")
        for trigger, exists in final_triggers.items():
            status = "✓ EXISTS" if exists else "✗ NOT FOUND"
            print(f"  - {trigger}: {status}")

        # Ask if we should rollback
        print("\n" + "="*60)
        response = input("\nMigration completed. Do you want to ROLLBACK? (y/N): ").strip().lower()

        if response == 'y':
            run_migration_down(cursor)
            conn.commit()

            # Verify rollback
            print("\n=== Verifying Rollback ===\n")
            final_tables = check_tables(cursor)
            final_triggers = check_triggers(cursor)

            print("Tables after rollback:")
            for table, exists in final_tables.items():
                status = "✓ REMOVED" if not exists else "✗ STILL EXISTS"
                print(f"  - {table}: {status}")

            print("\nTriggers after rollback:")
            for trigger, exists in final_triggers.items():
                status = "✓ REMOVED" if not exists else "✗ STILL EXISTS"
                print(f"  - {trigger}: {status}")
        else:
            print("\n✓ Migration changes kept")
            conn.commit()

        cursor.close()
        conn.close()

        print("\n=== SUCCESS ===\n")
        return 0

    except KeyboardInterrupt:
        print("\n\nAborted by user")
        return 130
    except Exception as e:
        print(f"\n✗ ERROR: {e}")
        import traceback
        traceback.print_exc()
        return 1

if __name__ == '__main__':
    sys.exit(main())
