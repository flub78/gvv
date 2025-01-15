-- First, let's analyze the existing role mappings:
-- role_id 1 = membre -> should map to types_roles.id 1 (user)
-- role_id 2 = admin -> should map to types_roles.id 9 (club-admin)
-- role_id 3 = bureau -> should map to types_roles.id 6 (ca)
-- role_id 7 = planchiste -> should map to types_roles.id 5 (planchiste)
-- role_id 8 = ca -> should map to types_roles.id 6 (ca)
-- role_id 9 = tresorier -> should map to types_roles.id 7 (tresorier)

-- Insert new records for each user, mapping their role_id to the appropriate types_roles_id
INSERT INTO user_roles_per_section (user_id, types_roles_id, section_id)
SELECT 
    u.id as user_id,
    CASE 
        WHEN u.role_id = 1 THEN 1   -- membre -> user
        WHEN u.role_id = 2 THEN 10  -- admin -> club-admin
        WHEN u.role_id = 3 THEN 7   -- bureau -> bureau
        WHEN u.role_id = 7 THEN 5   -- planchiste -> planchiste
        WHEN u.role_id = 8 THEN 6   -- ca -> ca
        WHEN u.role_id = 9 THEN 8   -- tresorier -> tresorier
        ELSE 1  -- default to basic user role
    END as types_roles_id,
    1 as section_id  -- Planeur section
FROM users u
WHERE NOT EXISTS (
    -- Only insert if the user doesn't already have a role for section 1
    SELECT 1 FROM user_roles_per_section urps 
    WHERE urps.user_id = u.id 
    AND urps.section_id = 1
);
