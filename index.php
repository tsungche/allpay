<?php
	$allpay = new ALLPAY;

	Class ALLPAY{
		function __construct(){
			global $db,$cms_cfg,$ws_array,$TPLMSG;
			include_once("config.php");
			//$this->allpay_checkcode();
		}
		
		#################################################
		
		// 訂單送出
		function allpay_send(
			$o_id=0, // 訂單編號
			$price=0, // 交易總金額
			$pay_desc=0, // 交易描述 (不可空值)
			$row=0, // 商品資訊 (array)
			$c_pay=0, // 交易方式
			$c_s_pay=0 // 選擇預設付款子項目
			//$i_rul=0, // 商品促銷網址
			//$remark=0, // 備註
		){
			global $db,$cms_cfg;
			
			$this->all_cfg["MerchantTradeNo"] = $o_id;
			$this->all_cfg["TotalAmount"] = $price;
			$this->all_cfg["ChoosePayment"] = $c_pay;
			$this->all_cfg["ChooseSubPayment"] = $c_s_pay;
			
			if(!empty($pay_desc)){
				$this->all_cfg["TradeDesc"] = $pay_desc;
			}
			
			
		}
		
		// 結果接收
		function allpay_get(){
			
		}
		
		// 檢查碼生成
		function allpay_checkcode(){
			ksort($this->all_cfg);
			
			foreach($this->all_cfg as $key => $value){
				if($key != "POST" && $key != "HashIV" && $key != "HashKey" && $key != "allpay_type" && !empty($value)){
					$all_value_array[] = $value;
				}
			}
			
			$this->all_code = md5(strtolower(urlencode($this->all_cfg["HashKey"].implode("&",$this->all_cfg).$this->all_cfg["HashIV"])));
		}
	}
?>