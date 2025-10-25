-- Update subcontract_requests status enum to include all workflow statuses
ALTER TABLE `subcontract_requests`
MODIFY COLUMN `status` ENUM('pending','awaiting_confirmation','in_progress','to_deliver','completed','cancelled') DEFAULT 'pending';
