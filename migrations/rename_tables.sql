-- SQL Script to Rename Tables
-- Reference: candidates -> vot_candidates, elections -> vot_elections, etc.

RENAME TABLE `candidates` TO `vot_candidates`;
RENAME TABLE `elections` TO `vot_elections`;
RENAME TABLE `election_history` TO `vot_election_history`;
RENAME TABLE `election_history_photos` TO `vot_election_history_photos`;
RENAME TABLE `schedule` TO `vot_schedules`;
RENAME TABLE `votes` TO `vot_votes`;
RENAME TABLE `voting_schedule` TO `vot_voting_schedule`;
