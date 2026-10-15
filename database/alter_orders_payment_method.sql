USE `it_phone_planet`;

ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(80) NULL AFTER `shipping_address`;
