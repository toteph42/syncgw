--
-- Delete all test records
--

DELETE FROM `calendars` WHERE `user_id` = 10001;
DELETE FROM `calendars` WHERE `user_id` = 10002;
DELETE FROM `calendars` WHERE `user_id` = 10003;

DELETE FROM `events` WHERE `event_id` = 10001;
DELETE FROM `events` WHERE `event_id` = 10002;
DELETE FROM `events` WHERE `event_id` = 10003;
DELETE FROM `events` WHERE `event_id` = 10004;
DELETE FROM `events` WHERE `event_id` = 10005;
DELETE FROM `events` WHERE `event_id` = 10006;
DELETE FROM `events` WHERE `event_id` = 10007;

DELETE FROM `attachments` WHERE `attachment_id` = 10001;
DELETE FROM `attachments` WHERE `attachment_id` = 10002;

DELETE FROM `contactgroups` WHERE `contactgroup_id` = 10001;
DELETE FROM `contactgroups` WHERE `contactgroup_id` = 10002;
DELETE FROM `contactgroups` WHERE `contactgroup_id` = 10003;
DELETE FROM `contactgroups` WHERE `contactgroup_id` = 10004;

DELETE FROM `contacts` WHERE `user_id` = 10001;
DELETE FROM `contacts` WHERE `user_id` = 10002;
DELETE FROM `contacts` WHERE `user_id` = 10004;

DELETE FROM `contactgroupmembers` WHERE `contactgroup_id` = 10001;
DELETE FROM `contactgroupmembers` WHERE `contactgroup_id` = 10002;
DELETE FROM `contactgroupmembers` WHERE `contactgroup_id` = 10003;
DELETE FROM `contactgroupmembers` WHERE `contactgroup_id` = 10004;

DELETE FROM `tasklists` WHERE `user_id` = 10001;
DELETE FROM `tasklists` WHERE `user_id` = 10002;
DELETE FROM `tasklists` WHERE `user_id` = 10003;

DELETE FROM `tasks` WHERE `task_id` = 10001;
DELETE FROM `tasks` WHERE `task_id` = 10002;
DELETE FROM `tasks` WHERE `task_id` = 10003;
DELETE FROM `tasks` WHERE `task_id` = 10004;

DELETE FROM `ddnotes` WHERE `id` = 10001;
DELETE FROM `ddnotes` WHERE `id` = 10002;
DELETE FROM `ddnotes` WHERE `id` = 10003;
DELETE FROM `ddnotes` WHERE `id` = 10004;
