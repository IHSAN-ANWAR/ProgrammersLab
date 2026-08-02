-- Add price columns to courses table
-- phpMyAdmin → SQL → paste → Go

ALTER TABLE courses 
    ADD COLUMN price          INT DEFAULT 0 AFTER sort_order,
    ADD COLUMN original_price INT DEFAULT 0 AFTER price,
    ADD COLUMN duration       VARCHAR(50) DEFAULT '' AFTER original_price;

-- Update default prices for existing courses
UPDATE courses SET price = 22000, original_price = 28000, duration = '3-4 Months' WHERE name = 'Full Stack Web Development';
UPDATE courses SET price = 15000, original_price = 20000, duration = '2-3 Months' WHERE name = 'Front-End Web Development';
UPDATE courses SET price = 18000, original_price = 22000, duration = '2-3 Months' WHERE name = 'PHP & MySQL';
UPDATE courses SET price = 10000, original_price = 15000, duration = '1-2 Months' WHERE name = 'WordPress';
UPDATE courses SET price = 30000, original_price = 35000, duration = '4-5 Months' WHERE name = 'React Native';
UPDATE courses SET price = 32000, original_price = 38000, duration = '4-5 Months' WHERE name = 'Flutter';
UPDATE courses SET price = 25000, original_price = 30000, duration = '3-4 Months' WHERE name = 'Android Development';
UPDATE courses SET price = 28000, original_price = 35000, duration = '3-4 Months' WHERE name = 'iOS Development';
UPDATE courses SET price = 20000, original_price = 25000, duration = '2-3 Months' WHERE name = 'Graphic Designing';
UPDATE courses SET price = 22000, original_price = 28000, duration = '2-3 Months' WHERE name = 'UI/UX Design';
UPDATE courses SET price = 5000,  original_price = 8000,  duration = '1 Month'    WHERE name = 'Canva Course';
UPDATE courses SET price = 25000, original_price = 30000, duration = '3-4 Months' WHERE name = 'Digital Marketing';
UPDATE courses SET price = 15000, original_price = 20000, duration = '2-3 Months' WHERE name = 'SEO Course';
UPDATE courses SET price = 18000, original_price = 22000, duration = '2-3 Months' WHERE name = 'Social Media Marketing';
UPDATE courses SET price = 15000, original_price = 20000, duration = '2-3 Months' WHERE name = 'Python';
UPDATE courses SET price = 12000, original_price = 15000, duration = '2-3 Months' WHERE name = 'C++ Course';
UPDATE courses SET price = 15000, original_price = 18000, duration = '2-3 Months' WHERE name = 'Java Course';
UPDATE courses SET price = 12000, original_price = 15000, duration = '2 Months'   WHERE name = 'Database Management';
UPDATE courses SET price = 15000, original_price = 20000, duration = '3-4 Months' WHERE name = 'Freelancing';
UPDATE courses SET price = 8000,  original_price = 12000, duration = '1-2 Months' WHERE name = 'Video Editing';
UPDATE courses SET price = 8000,  original_price = 12000, duration = '1-2 Months' WHERE name = 'MS Office / CIT';
