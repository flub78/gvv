# V2 Authorization System Smoke Test Cases

Based on analysis of the GVV authorization system, here are the test cases for basic smoke testing:

## Role Definitions (from migrations)
- ID 1: `user` - Basic user access
- ID 2: `auto_planchiste` - Can edit own flight logs  
- ID 5: `planchiste` - Can edit all flights in section
- ID 6: `ca` - Board member access
- ID 7: `board` (bureau) - Full section management
- ID 8: `tresorier` - Financial management in section
- ID 9: `super_tresorier` - Financial management across all sections
- ID 10: `admin` (club-admin) - Full system access

## Smoke Test Cases Table

| Role (+ user)      | Granted Access                           | Denied Access                           |
|-------------------|------------------------------------------|----------------------------------------|
| **user**          | `/auth/login`                           | `/vols_planeur/create`                 |
|                   | `/membre/page` (own profile)            | `/vols_planeur/edit/123`               |
|                   | `/calendar/index`                       | `/membre/create`                       |
|                   | `/compta/mon_compte` (own account)      | `/compta/ecritures`                    |
|                   | `/vols_avion/vols_du_pilote/[own_id]`   | `/terrains/page`                       |
|                   | `/welcome/index`                        | `/authorization/user_roles`           |
|                   |                                          | `/backend/users`                       |
|                   |                                          | `/admin/index`                         |
| **auto_planchiste** | `/vols_planeur/create`                  | `/membre/edit/123` (other's profile)   |
|                   | `/vols_planeur/edit` (own flights)      | `/compta/ecritures`                    |
|                   | `/membre/page` (own profile)            | `/terrains/page`                       |
|                   | `/calendar/index`                       | `/authorization/user_roles`           |
|                   | `/compta/mon_compte`                    | `/backend/users`                       |
| **planchiste**    | `/vols_planeur/create`                  | `/compta/ecritures`                    |
|                   | `/vols_planeur/edit` (all in section)   | `/compta/comptes`                      |
|                   | `/vols_planeur/page`                    | `/terrains/create`                     |
|                   | `/membre/page` (all in section)         | `/authorization/user_roles`           |
|                   | `/calendar/index`                       | `/backend/users`                       |
|                   | `/membre/edit` (members in section)     | `/admin/index`                         |
| **ca**            | `/terrains/page`                        | `/compta/ecritures/create`             |
|                   | `/membre/page` (all in section)         | `/compta/comptes/edit`                 |
|                   | `/vols_planeur/page` (all in section)   | `/authorization/user_roles`           |
|                   | `/calendar/manage`                      | `/backend/users`                       |
|                   | `/compta/rapports` (view only)          | `/admin/index`                         |
|                   | `/terrains/edit`                        | `/users/create`                        |
| **board** (bureau) | `/membre/create`                       | `/compta/comptes/delete`               |
|                   | `/membre/edit` (all in section)         | `/authorization/user_roles`           |
|                   | `/vols_planeur/page` (all in section)   | `/backend/users`                       |
|                   | `/compta/rapports` (full access)        | `/admin/index`                         |
|                   | `/terrains/page`                        | `/users/delete`                        |
|                   | `/calendar/manage`                      |                                        |
| **tresorier**     | `/compta/ecritures`                     | `/membre/delete`                       |
|                   | `/compta/ecritures/create`              | `/authorization/user_roles`           |
|                   | `/compta/comptes/edit`                  | `/backend/users`                       |
|                   | `/compta/rapports`                      | `/admin/index`                         |
|                   | `/compta/facturation`                   | `/terrains/delete`                     |
|                   | `/membre/page` (financial data)         | `/users/create`                        |
| **super_tresorier** | `/compta/ecritures` (all sections)    | `/authorization/user_roles`           |
|                   | `/compta/comptes` (all sections)        | `/backend/users`                       |
|                   | `/compta/rapports` (all sections)       | `/admin/index`                         |
|                   | `/compta/facturation` (all sections)   | `/users/delete`                        |
|                   | `/membre/page` (financial data, all)    | `/terrains/delete`                     |
| **admin**         | `/authorization/user_roles`             | *None - admin has full access*        |
|                   | `/backend/users`                        |                                        |
|                   | `/admin/index`                          |                                        |
|                   | `/users/create`                         |                                        |
|                   | `/users/edit`                           |                                        |
|                   | `/users/delete`                         |                                        |
|                   | `/compta/ecritures` (all sections)      |                                        |
|                   | `/terrains/create`                      |                                        |
|                   | `/terrains/edit`                        |                                        |
|                   | `/terrains/delete`                      |                                        |

## Test Implementation Notes

1. **Section Context**: Most tests should be run with a specific section_id to verify section-based permissions work correctly.

2. **Data Ownership**: For `user` and `auto_planchiste` roles, test both:
   - Access to own data (should be granted)
   - Access to other users' data (should be denied)

3. **Cross-Section Access**: For roles like `super_tresorier` and `admin`, verify they can access data across multiple sections.

4. **HTTP Response Codes**: 
   - Granted access: HTTP 200 (success) or appropriate redirect
   - Denied access: HTTP 403 (forbidden) or redirect to access denied page

5. **Test Data Setup**: Each test should have:
   - A test user with the specific role
   - Test data in a known section
   - Cross-section test data for multi-section roles

## Suggested Test Structure

```php
class AuthorizationSmokeTest extends CI_TestCase 
{
    public function test_user_role_granted_access() 
    {
        // Test user can access /membre/page with own ID
        // Test user can access /calendar/index
        // etc.
    }
    
    public function test_user_role_denied_access() 
    {
        // Test user cannot access /vols_planeur/create
        // Test user cannot access /terrains/page
        // etc.
    }
    
    // Repeat for each role...
}
```

This table provides a solid foundation for smoke testing the basic authorization mechanisms across all role types.