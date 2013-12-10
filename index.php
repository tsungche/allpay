<?php
	$allpay = new ALLPAY;

	Class ALLPAY{
		function __construct(){
			global $db,$cms_cfg,$ws_array,$TPLMSG;
			include_once("config.php");
		}
		
		#################################################
		
		// 訂單送出
		function allpay_send(
			$o_id=0, // 訂單編號
			$price=0, // 交易總金額
			$pay_desc=0, // 交易描述 (不可空值)
			$shopping=0, // 商品資訊 (array)
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
			
			if(!empty($shopping) && is_array($shopping)){
				foreach($shopping as $sess_code => $row){
					$p_name_array[] = $row["p_name"];
					$p_num_array[] = $_SESSION[$cms_cfg['sess_cookie_name']]["amount"][$sess_code];
					
	                if(!empty($_SESSION[$cms_cfg['sess_cookie_name']]["MEMBER_DISCOUNT"]) && $_SESSION[$cms_cfg['sess_cookie_name']]["MEMBER_DISCOUNT"]!=100){
	                    $p_price_array[] = floor($_SESSION[$cms_cfg['sess_cookie_name']]["MEMBER_DISCOUNT"] / 100 * $row["p_special_price"]);
	                }else{
	                    $p_price_array[] = $row["p_special_price"];
	                }
				}
				
				if($c_pay == "Alipay"){
					$this->all_cfg["AlipayItemName"] = implode("#",$p_name_array);
					$this->all_cfg["AlipayItemCounts"] = implode("#",$p_num_array);
					$this->all_cfg["AlipayItemPrice"] = implode("#",$p_price_array);
					
					$this->all_cfg["Email"] = $_REQUEST["m_email"];
					$this->all_cfg["PhoneNo"] = $_REQUEST["m_tel"];
					$this->all_cfg["UserName"] = $_REQUEST["m_name"];
				}else{
					$this->all_cfg["ItemName"] = implode("#",$p_name_array);
				}
			}
			
			$this->allpay_code = $this->allpay_checkcode();
			
			foreach($this->all_cfg as $key => $value){
				if($key != "POST" && $key != "PATH" && $key != "HashIV" && $key != "HashKey" && $key != "allpay_type" && !empty($value)){
					$all_value_array[] = $key.'='.$value;
				}
			}
			
			if(count($all_value_array) > 0 && is_array($all_value_array)){
				$this->allpay_value = implode("&",$all_value_array);
			}
			
			if(!empty($this->allpay_value)){
				header('POST '.$this->all_cfg["PATH"].' HTTP/1.1');
				header('Host: '.$this->all_cfg["POST"]);
				header('Connection: close');
				header('Content-type: application/x-www-form-urlencoded');
				header('Content-length: ' . strlen($this->allpay_value));
				header('');
				header($this->allpay_value);
				
				return true;
			}else{
				return false;
			}
		}
		
		// 結果接收
		function allpay_get(){
			
		}
		
		// 檢查碼生成
		function allpay_checkcode(){
			ksort($this->all_cfg);
			
			foreach($this->all_cfg as $key => $value){
				if($key != "POST" && $key != "PATH" && $key != "HashIV" && $key != "HashKey" && $key != "allpay_type" && !empty($value)){
					$all_value_array[] = $value;
				}
			}
			
			return md5(strtolower(urlencode($this->all_cfg["HashKey"].implode("&",$this->all_cfg).$this->all_cfg["HashIV"])));
		}
	}
?>