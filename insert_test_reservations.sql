-- Test data for reservations table
INSERT INTO reservations (
    aircraft_id, 
    start_datetime, 
    end_datetime, 
    pilot_member_id, 
    instructor_member_id, 
    purpose, 
    status, 
    notes, 
    section_id, 
    created_by
) VALUES
-- Reservation 1: Training flight today morning
('F-CXXX', '2026-01-08 10:00:00', '2026-01-08 11:30:00', 'user1', 'user2', 'Training flight', 'confirmed', 'Standard training session', 1, 'admin'),

-- Reservation 2: Cross-country tomorrow
('F-CYYY', '2026-01-09 09:00:00', '2026-01-09 13:00:00', 'user3', NULL, 'Cross-country', 'pending', 'Long distance flight', 1, 'admin'),

-- Reservation 3: Practice landing today afternoon
('F-CZZZ', '2026-01-08 14:00:00', '2026-01-08 15:30:00', 'user2', NULL, 'Practice landing', 'confirmed', 'Solo flight', 1, 'admin'),

-- Reservation 4: Training next week (section 2)
('F-CXXX', '2026-01-15 10:00:00', '2026-01-15 11:30:00', 'user4', 'user1', 'Training flight', 'pending', 'New pilot training', 2, 'admin'),

-- Reservation 5: Extended flight Sunday
('F-CYYY', '2026-01-12 08:00:00', '2026-01-12 16:00:00', 'user1', NULL, 'Competition practice', 'confirmed', 'Full day flight training', 1, 'admin'),

-- Reservation 6: Cancelled reservation (for testing filter)
('F-CZZZ', '2026-01-11 10:00:00', '2026-01-11 11:00:00', 'user3', NULL, 'Cancelled event', 'cancelled', 'Event was cancelled', 1, 'admin');
