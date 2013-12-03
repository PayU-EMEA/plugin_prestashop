<?php
/**
 *  ver. 1.9.11
 *  PayU Payment Modules
 *
 *  @copyright  Copyright 2012 by PayU
 *  @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
 *  http://www.payu.com
 *  http://twitter.com/openpayu
 */

class payu_session extends ObjectModel
{
	public 		$id;
	
	public 		$id_payu_session;
	
	public 		$id_order;
	
	public 		$id_cart;
	
	public 		$sid;
	
	public		$status;
	
	public		$create_at;
	
	public		$update_at;
	
 	protected 	$fieldsRequired = array('id_order', 'id_cart', 'status', 'sid');
 	protected 	$fieldsSize = array('status' => 64);
 	protected 	$fieldsValidate = array('id_payu_session' => 'isUnsignedInt', 'id_order' => 'isInt', 'id_cart' => 'isInt');
	
	protected 	$table = 'payu_session';
	protected 	$identifier = 'id_payu_session';	
	
	public function getFields()
	{
	 	parent::validateFields();
		
		$fields['id_payu_session'] = (int)$this->id_payu_session;
		$fields['id_order'] = (int)$this->id_order;
		$fields['id_cart'] = (int)$this->id_cart;
		$fields['sid'] = $this->sid;
		$fields['status'] = $this->status;
		
		if (empty($this->create_at))
			$this->create_at = date('Y-m-d H:i:s');
		$fields['create_at'] = pSQL($this->create_at);
		
		if (empty($this->update_at))
			$this->update_at = date('Y-m-d H:i:s');
		$fields['update_at'] = pSQL($this->update_at);
		
		return $fields;
	}
	
	public function add($autodate = true, $nullValues = true)
	{
	 	return parent::add($autodate, $nullValues);
	}
	
	public static function existsBySID($sid)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_payu_session`
		FROM `'._DB_PREFIX_.'payu_session`
		WHERE `sid`="'.$sid.'"');
		
		if($result){
			return (int)($result['id_payu_session']);
		} else {
			return false;
		}
	}
	
	public static function existsByOrderId($orderId)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_payu_session`
		FROM `'._DB_PREFIX_.'payu_session`
		WHERE `id_order`='.(int)($orderId));
		
		if($result){
			return (int)($result['id_payu_session']);
		} else {
			return false;
		}
	}
	
	public static function existsByOrderIdAndSID($orderId, $sid, $cartId=0)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_payu_session`
		FROM `'._DB_PREFIX_.'payu_session`
		WHERE `sid`="'.$sid.'" ');
		
		if($result){
			return (int)($result['id_payu_session']);
		} else {
			return false;
		}
	}
	
	public static function existsByCartId($cartId)
	{
		$result = Db::getInstance()->getRow('
		SELECT `id_payu_session`
		FROM `'._DB_PREFIX_.'payu_session`
		WHERE `id_cart`='.(int)($cartId));
		
		if($result){
			return (int)($result['id_payu_session']);
		} else {
			return false;
		}
	}
	
}