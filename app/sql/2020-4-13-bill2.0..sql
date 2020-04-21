ALTER TABLE `ps_bill`
ADD COLUMN `recharge_amount`  decimal(12,2) NULL DEFAULT 0 COMMENT '支付大与账单金额的一个充值金额' AFTER `prefer_entry_amount`,
ADD COLUMN `deduct_amount`  decimal(12,2) NULL DEFAULT 0 COMMENT '充值金额抵扣支付的金额' AFTER `recharge_amount`;

