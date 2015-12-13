<?php
/**
*
*/
class LogTransaction extends Eloquent
{
    public $primaryKey = 'l_id';
    public $timestamps = false;

    public static $TYPE_TRADE = 1;
    public static $TYPE_REFUND = 2;
    public static $TYPE_REPAYMENT = 3;
    public static $TYPE_LOAN = 4;
    public static $TYPE_PROFIT = 5;

    public static $CATE_CROWDFUNDING = 1;
    public static $CATE_PRODUCT = 2;
    public static $CATE_AUCTION = 3;

    public static $OPERATOR_QNCK = 1;
    public static $OPERATOR_USER = 2;

    public static $PAYMENT_WECHAT = 1;
    public static $PAYMENT_ALIPAY = 2;

    private function baseValidate()
    {
        $validator = Validator::make(
            ['transaction_id' => $this->transaction_id],
            ['transaction_id' => 'required']
        );
        if ($validator->fails()) {
            $msg = $validator->messages();
            throw new Exception($msg->first(), 1);
        } else {
            return true;
        }
    }

    public function addLog()
    {
        $this->baseValidate();
        $this->created_at = Tools::getNow();
        return $this->save();
    }
}
