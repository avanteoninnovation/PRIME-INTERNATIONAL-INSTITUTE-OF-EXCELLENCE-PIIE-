UPDATE global_settings SET value = '1' WHERE `key` = 'frontend_view';
INSERT INTO global_settings (`key`, value) SELECT 'frontend_view', '1' WHERE NOT EXISTS (SELECT 1 FROM global_settings WHERE `key` = 'frontend_view');
