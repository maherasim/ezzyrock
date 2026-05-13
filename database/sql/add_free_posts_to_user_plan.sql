ALTER TABLE `user_plan`
  ADD COLUMN `free_posts` int unsigned NOT NULL DEFAULT 0 AFTER `trial_period`;
