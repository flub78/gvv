INSERT IGNORE INTO user_roles_per_section (user_id, types_roles_id, section_id, granted_by, granted_at, revoked_at, notes)
SELECT DISTINCT u.id, 1, c.club, NULL, NOW(), NULL, 'Auto-granted: compte 411 member'
FROM comptes c
JOIN users u ON c.pilote = u.username
WHERE c.codec = '411' AND c.actif = 1 AND c.pilote IS NOT NULL AND c.club IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM user_roles_per_section urps
    WHERE urps.user_id = u.id AND urps.types_roles_id = 1 
    AND urps.section_id = c.club AND urps.revoked_at IS NULL
);
