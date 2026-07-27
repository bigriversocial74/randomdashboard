SET NAMES utf8mb4;
SET time_zone='+00:00';

-- Sections 24–25 Integration Audit hardening.
-- Import after the Section 24 and Section 25 deferred migrations.

INSERT IGNORE INTO permissions(permission_key,area,action,description) VALUES
('entities.view','entities','view','View business entity hierarchy and integrations'),
('entities.create','entities','create','Create business entities and templates'),
('entities.edit','entities','edit','Edit entity relationships, authorities, scopes, and mappings'),
('entities.apply','entities','apply','Apply reviewed entity templates'),
('entities.review','entities','review','Review templates, mappings, and conflicts'),
('entities.administer','entities','administer','Administer entity hierarchy and integration policy'),
('entities.export','entities','export','Export business entity reports'),
('process_mapping.view','process_mapping','view','View visual process maps, instances, controls, and analytics'),
('process_mapping.edit','process_mapping','edit','Create draft process maps, steps, transitions, and layouts'),
('process_mapping.publish','process_mapping','publish','Publish independently reviewed and approved process versions'),
('process_mapping.execute','process_mapping','execute','Start and advance governed process instances'),
('process_mapping.analyze','process_mapping','analyze','View cycle time, bottleneck, exception, and compliance intelligence'),
('process_mapping.export','process_mapping','export','Export the governed process catalog'),
('process_mapping.administer','process_mapping','administer','Administer process templates, assignments, controls, and integration mappings');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='system_administrator'
  AND p.permission_key IN(
    'entities.view','entities.create','entities.edit','entities.apply','entities.review','entities.administer','entities.export',
    'process_mapping.view','process_mapping.edit','process_mapping.publish','process_mapping.execute','process_mapping.analyze','process_mapping.export','process_mapping.administer'
  );

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='executive'
  AND p.permission_key IN('entities.view','entities.review','entities.export','process_mapping.view','process_mapping.analyze','process_mapping.export');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='company_administrator'
  AND p.permission_key IN('entities.view','entities.edit','entities.review','entities.export','process_mapping.view','process_mapping.edit','process_mapping.execute','process_mapping.analyze','process_mapping.export');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='procurement_manager'
  AND p.permission_key IN('entities.view','entities.review','entities.export','process_mapping.view','process_mapping.edit','process_mapping.execute','process_mapping.analyze','process_mapping.export');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='data_contributor'
  AND p.permission_key IN('entities.view','process_mapping.view','process_mapping.execute');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='reviewer'
  AND p.permission_key IN('entities.view','entities.review','process_mapping.view','process_mapping.publish','process_mapping.execute','process_mapping.analyze');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p
WHERE r.code='read_only'
  AND p.permission_key IN('entities.view','process_mapping.view','process_mapping.analyze');

UPDATE business_process_step_instances si
JOIN business_process_instances i ON i.id=si.instance_id
JOIN business_process_steps s ON s.id=si.step_id
JOIN business_process_lanes l ON l.id=s.lane_id
SET si.assigned_entity_id=CASE
    WHEN l.participant_type='external' THEN NULL
    ELSE COALESCE(l.entity_id,i.entity_id)
END
WHERE si.status IN('pending','active','exception');

UPDATE business_process_lanes l
JOIN business_process_versions v ON v.id=l.version_id
JOIN business_processes p ON p.id=v.process_id
SET l.entity_id=(SELECT id FROM business_entities WHERE entity_code='GRUBER-PROCUREMENT' ORDER BY id LIMIT 1)
WHERE p.process_code='PROC-P2P' AND l.lane_code='shared_procurement';

UPDATE business_process_lanes l
JOIN business_process_versions v ON v.id=l.version_id
JOIN business_processes p ON p.id=v.process_id
SET l.entity_id=(SELECT id FROM business_entities WHERE entity_code='GRUBER-ACCOUNTS-PAYABLE' ORDER BY id LIMIT 1)
WHERE p.process_code='PROC-P2P' AND l.lane_code='shared_finance';

UPDATE business_process_lanes l
JOIN business_process_versions v ON v.id=l.version_id
JOIN business_processes p ON p.id=v.process_id
SET l.entity_id=(SELECT id FROM business_entities WHERE entity_code='COMP-GPS' ORDER BY id LIMIT 1)
WHERE p.process_code='PROC-P2P' AND l.lane_code IN('requesting_business','receiving_business');

INSERT IGNORE INTO schema_migrations(version,description)
VALUES('5.5-sections24-25-audit','Sections 24-25 multi-corporation governance, process orchestration authorization, lane ownership, and role hardening');
