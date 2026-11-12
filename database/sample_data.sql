-- Fictional demonstration records. Safe to re-import without duplicate sample items.
-- Does not replace or remove existing reports. Gmail-style addresses are illustrative and unverified; do not send mail to them.
USE lost_found_db;
SET time_zone = '+08:00';
START TRANSACTION;
INSERT INTO users (name,email) SELECT 'Mika Demo','mika.santos.zppsu4827@gmail.com' WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='mika.santos.zppsu4827@gmail.com');
INSERT INTO users (name,email) SELECT 'Alex Demo','alex.reyes.zppsu6139@gmail.com' WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='alex.reyes.zppsu6139@gmail.com');
INSERT INTO users (name,email) SELECT 'Jamie Demo','jamie.cruz.zppsu7524@gmail.com' WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='jamie.cruz.zppsu7524@gmail.com');
INSERT INTO users (name,email) SELECT 'Sam Demo','sam.ramos.zppsu9368@gmail.com' WHERE NOT EXISTS (SELECT 1 FROM users WHERE email='sam.ramos.zppsu9368@gmail.com');

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Wireless Earbuds in White Case','Electronics','found','White wireless earbuds in a small charging case. Found after an afternoon class. Describe the case markings to verify ownership.','Main Library, reading area',DATE_SUB(CURDATE(),INTERVAL 1 DAY),NULL,u.id,'mika.santos.zppsu4827@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 24 HOUR),'approved'
FROM users u WHERE u.email='mika.santos.zppsu4827@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Wireless Earbuds in White Case' AND i.contact_email='mika.santos.zppsu4827@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Black Scientific Calculator','Electronics','lost','A black scientific calculator used for engineering classes. It has a protective sliding cover and a small label on the back.','College of Engineering, Room 204',DATE_SUB(CURDATE(),INTERVAL 2 DAY),NULL,u.id,'alex.reyes.zppsu6139@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 49 HOUR),'approved'
FROM users u WHERE u.email='alex.reyes.zppsu6139@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Black Scientific Calculator' AND i.contact_email='alex.reyes.zppsu6139@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Student ID with Maroon Lanyard','IDs','found','Student identification card attached to a maroon lanyard. Personal information is kept private; please describe the card to claim it.','Administration Building lobby',DATE_SUB(CURDATE(),INTERVAL 0 DAY),NULL,u.id,'jamie.cruz.zppsu7524@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 2 HOUR),'approved'
FROM users u WHERE u.email='jamie.cruz.zppsu7524@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Student ID with Maroon Lanyard' AND i.contact_email='jamie.cruz.zppsu7524@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Library Card in Clear Holder','IDs','claimed','A library card in a clear plastic holder was returned after its owner verified the identifying information.','Main Library circulation desk',DATE_SUB(CURDATE(),INTERVAL 4 DAY),NULL,u.id,'sam.ramos.zppsu9368@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 99 HOUR),'approved'
FROM users u WHERE u.email='sam.ramos.zppsu9368@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Library Card in Clear Holder' AND i.contact_email='sam.ramos.zppsu9368@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Blue Calculus Notebook','Books','lost','A blue spiral notebook containing handwritten calculus notes. Last seen after a morning lecture.','College of Engineering, study area',DATE_SUB(CURDATE(),INTERVAL 1 DAY),NULL,u.id,'alex.reyes.zppsu6139@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 28 HOUR),'approved'
FROM users u WHERE u.email='alex.reyes.zppsu6139@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Blue Calculus Notebook' AND i.contact_email='alex.reyes.zppsu6139@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Introduction to Programming Textbook','Books','found','A programming textbook with a dark blue cover and several page tabs. Describe an identifying detail to verify ownership.','Computer Laboratory 2',DATE_SUB(CURDATE(),INTERVAL 3 DAY),NULL,u.id,'mika.santos.zppsu4827@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 77 HOUR),'approved'
FROM users u WHERE u.email='mika.santos.zppsu4827@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Introduction to Programming Textbook' AND i.contact_email='mika.santos.zppsu4827@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Three Keys on a Green Keyring','Keys','found','Three metal keys attached to a green keyring. Found near the covered walkway. Please describe the keyring design.','Covered Walkway near the canteen',DATE_SUB(CURDATE(),INTERVAL 0 DAY),NULL,u.id,'jamie.cruz.zppsu7524@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 6 HOUR),'approved'
FROM users u WHERE u.email='jamie.cruz.zppsu7524@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Three Keys on a Green Keyring' AND i.contact_email='jamie.cruz.zppsu7524@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Motorcycle Key with Black Strap','Keys','lost','A motorcycle key attached to a short black fabric strap. Last seen near the motorcycle parking area.','Campus motorcycle parking area',DATE_SUB(CURDATE(),INTERVAL 2 DAY),NULL,u.id,'sam.ramos.zppsu9368@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 55 HOUR),'approved'
FROM users u WHERE u.email='sam.ramos.zppsu9368@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Motorcycle Key with Black Strap' AND i.contact_email='sam.ramos.zppsu9368@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Maroon Water Bottle','Personal Items','found','A reusable maroon water bottle with a screw-top lid. Found on a bench after lunch. Describe any unique markings.','Student Center benches',DATE_SUB(CURDATE(),INTERVAL 1 DAY),NULL,u.id,'mika.santos.zppsu4827@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 32 HOUR),'approved'
FROM users u WHERE u.email='mika.santos.zppsu4827@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Maroon Water Bottle' AND i.contact_email='mika.santos.zppsu4827@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Navy Blue Umbrella','Personal Items','claimed','A compact navy blue umbrella with a wrist strap. Returned to its owner after verification.','Canteen entrance',DATE_SUB(CURDATE(),INTERVAL 5 DAY),NULL,u.id,'jamie.cruz.zppsu7524@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 129 HOUR),'approved'
FROM users u WHERE u.email='jamie.cruz.zppsu7524@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Navy Blue Umbrella' AND i.contact_email='jamie.cruz.zppsu7524@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Rolled Architecture Drawing','Other','lost','A rolled drawing sheet inside a cardboard tube. Last seen near the drafting room after a class presentation.','Technology Building, drafting room',DATE_SUB(CURDATE(),INTERVAL 3 DAY),NULL,u.id,'alex.reyes.zppsu6139@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 82 HOUR),'approved'
FROM users u WHERE u.email='alex.reyes.zppsu6139@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Rolled Architecture Drawing' AND i.contact_email='alex.reyes.zppsu6139@gmail.com') LIMIT 1;

INSERT INTO items (title,category,status,description,location,date_reported,image_path,reporter_id,contact_email,contact_phone,created_at,moderation_status)
SELECT 'Small Art Supply Pouch','Other','claimed','A canvas pouch containing pencils and drawing tools. The owner described the contents and collected it.','Arts and Design classroom',DATE_SUB(CURDATE(),INTERVAL 6 DAY),NULL,u.id,'sam.ramos.zppsu9368@gmail.com',NULL,DATE_SUB(NOW(),INTERVAL 155 HOUR),'approved'
FROM users u WHERE u.email='sam.ramos.zppsu9368@gmail.com' AND NOT EXISTS (SELECT 1 FROM items i WHERE i.title='Small Art Supply Pouch' AND i.contact_email='sam.ramos.zppsu9368@gmail.com') LIMIT 1;

UPDATE items SET image_path='https://down-br.img.susercontent.com/file/sg-11134201-7rdwu-md25a3bfp3kkc1' WHERE title='Wireless Earbuds in White Case' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://mrpen.com/cdn/shop/files/1-13-2.jpg?v=1750096376&width=990' WHERE title='Black Scientific Calculator' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://5.imimg.com/data5/SELLER/Default/2023/11/363078437/YJ/ZW/OR/3573903/mp-04-two-side-sticker-pasting-card-54-86-mm-500x500.jpg' WHERE title='Student ID with Maroon Lanyard' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://www.nfc24.pl/_next/image?q=75&url=https%3A%2F%2Fecommercenfc24.blob.core.windows.net%2Fimages%2Ff8562883c0349b196b7c62275e74d551.jpg&w=640' WHERE title='Library Card in Clear Holder' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://i5.walmartimages.com/asr/da37927e-48f9-4a5e-aa6a-fc84ab1a57a8.0a749599ac35f01cefa54951f0e0ac28.jpeg?odnBg=FFFFFF&odnHeight=768&odnWidth=768' WHERE title='Blue Calculus Notebook' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://images.pangobooks.com/book_images/nvsGsQ9DyNNv1FsqqCW4LczYHm53/1650237548270_nvsGsQ9DyNNv1FsqqCW4LczYHm53?crop=1%3A1&quality=85&width=1200' WHERE title='Introduction to Programming Textbook' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://www.bluechipbranding.co.za/content/images/thumbs/0098427_keyring-nevek.png' WHERE title='Three Keys on a Green Keyring' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://images.meesho.com/images/products/841404192/l9cen_512.jpg' WHERE title='Motorcycle Key with Black Strap' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://images.lululemon.com/is/image/lululemon/LU9CM1S_076023_1?size=1200%2C1200' WHERE title='Maroon Water Bottle' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://s.eslite.com/upload/product/o/2682027280009/20210603050127267701.jpg' WHERE title='Navy Blue Umbrella' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://i.pinimg.com/originals/53/ea/e0/53eae02a47f00cbf748395780ec7248b.jpg' WHERE title='Rolled Architecture Drawing' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
UPDATE items SET image_path='https://img.shop-pro.jp/PA01000/164/product/167404944_o3.jpg?cmsp_timestamp=20220328164210' WHERE title='Small Art Supply Pouch' AND contact_email IN ('mika.santos.zppsu4827@gmail.com','alex.reyes.zppsu6139@gmail.com','jamie.cruz.zppsu7524@gmail.com','sam.ramos.zppsu9368@gmail.com') AND (image_path IS NULL OR image_path LIKE 'https://%');
COMMIT;
