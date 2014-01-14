<?php
	$allpay = new ALLPAY;

	Class ALLPAY{
		function __construct(){
			global $db,$cms_cfg,$ws_array,$TPLMSG;
			include_once("config.php");
			
			if(!empty($_POST["MerchantTradeNo"]) && empty($_REQUEST["o_id"])){
				$this->allpay_respone();
			}
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

			// 基本設定
			$this->all_cfg["MerchantTradeNo"] = $o_id;
			$this->all_cfg["TotalAmount"] = $price;
			$this->all_cfg["ChoosePayment"] = $c_pay;
			$this->all_cfg["ChooseSubPayment"] = $c_s_pay;
			$this->all_cfg["OrderResultURL"] = $cms_cfg["base_url"].'member.php?func=m_zone&mzt=order&type=detail&o_id='.$o_id;

			// 交易描述
			if(!empty($pay_desc)){
				$this->all_cfg["TradeDesc"] = $pay_desc;
			}else{
				$this->all_cfg["TradeDesc"] = 'TEST';
			}
			
			//取得商品資訊
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
				
				// 判斷是否為支付寶
				if($c_pay == "Alipay"){
					$this->all_cfg["AlipayItemName"] = implode("#",$p_name_array);
					$this->all_cfg["AlipayItemCounts"] = implode("#",$p_num_array);
					$this->all_cfg["AlipayItemPrice"] = implode("#",$p_price_array);

					$this->all_cfg["Email"] = $_REQUEST["m_email"];
					$this->all_cfg["PhoneNo"] = $_REQUEST["m_tel"];
					$this->all_cfg["UserName"] = $_REQUEST["m_name"];
				}else{
					foreach($p_name_array as $p_key => $p_val){
						$p_array[] = $p_val.'X'.$p_num_array[$p_key];
					}
					
					$this->all_cfg["ItemName"] = implode("#",$p_array);
				}
			}
			
			// 組合所有參數
			ksort($this->all_cfg);
			foreach($this->all_cfg as $key => $value){
				if($key != "POST" && $key != "PATH" && $key != "HashIV" && $key != "HashKey" && $key != "allpay_type" && !empty($value)){
					$all_value_array[$key] = $value;
					$all_code_array[] = $key.'='.$value;
				}
			}
			
			// 取得檢查碼
			$this->allpay_code = $this->allpay_checkcode($all_code_array);
			
			// 組合訂單資訊
			$this->allpay_send_form($all_value_array);
		}

		// 組合訂單資訊
		function allpay_send_form($all_val=0){
			
			if(!empty($all_val) && is_array($all_val)){
				unset($input_str);
				foreach($all_val as $key => $val){
					$input_str[$key] = '<input type="hidden" name="'.$key.'" value="'.$val.'">';
				}
				
				if(count($input_str) > 0){
					$input_add = implode('',$input_str);
				}
				
				$form = '
					<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
					<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
					
					<body>
					<form name="allpay_form" method="post" action="'.$this->all_cfg["POST"].$this->all_cfg["PATH"].'">
						<input type="hidden" name="CheckMacValue" value="'.$this->allpay_code.'">
						'.$input_add.'
					</form>
					</body>
					</html>
					
					<script>
						document.allpay_form.submit();
					</script>
				';
				
				echo $form;
				
				//檢查用
				#echo implode('<br /><br />',$this->ck);
				
				return true;
			}else{
				return false;
			}
		}
		
		// 結果接收
		function allpay_respone(){
			global $db,$cms_cfg,$ws_array,$TPLMSG;
			
			ksort($_POST);
			foreach($_POST as $key => $value){
				if($key != "CheckMacValue"){
					$all_post_array[] = $key.'='.$value;
				}
			}

			$ckmac_key = strtoupper($this->allpay_checkcode($all_post_array));
			
			if($ckmac_key == $_POST["CheckMacValue"]){
	            $sql="
	                insert into ".$cms_cfg['tb_prefix']."_allpay_order (
	                    o_id,
	                    MerchantID,
	                    RtnCode,
	                    RtnMsg,
	                    TradeNo,
	                    TradeAmt,
	                    PaymentDate,
	                    PaymentType,
	                    PaymentTypeChargeFee,
	                    TradeDate,
	                    SimulatePaid,
	                    CheckMacValue
	                ) values (
	                    '".$_POST["MerchantTradeNo"]."',
	                    '".$_POST["MerchantID"]."',
	                    '".$_POST["RtnCode"]."',
	                    '".$_POST["RtnMsg"]."',
	                    '".$_POST["TradeNo"]."',
	                    '".$_POST["TradeAmt"]."',
	                    '".$_POST["PaymentDate"]."',
	                    '".$_POST["PaymentType"]."',
	                    '".$_POST["PaymentTypeChargeFee"]."',
	                    '".$_POST["TradeDate"]."',
	                    '".$_POST["SimulatePaid"]."',
	                    '".$_POST["CheckMacValue"]."'
	                )";
	            $rs = $db->query($sql);
	            
	            echo '1|OK';
            }else{
            	echo '0|ErrorMessage';
            }
            
            //header("refresh:2;url=".$cms_cfg["base_root"]);
            exit;
		}
		
		// 檢查碼生成
		function allpay_checkcode($all_code_array){
			
			#檢查用
			#$this->ck[1] = 'HashKey='.$this->all_cfg["HashKey"].'&'.implode("&",$all_value_array).'&HashIV='.$this->all_cfg["HashIV"];
			#$this->ck[2] = urlencode('HashKey='.$this->all_cfg["HashKey"].'&'.implode("&",$all_value_array).'&HashIV='.$this->all_cfg["HashIV"]);
			#$this->ck[3] = strtolower(urlencode('HashKey='.$this->all_cfg["HashKey"].'&'.implode("&",$all_value_array).'&HashIV='.$this->all_cfg["HashIV"]));
			
			return md5(strtolower(urlencode('HashKey='.$this->all_cfg["HashKey"].'&'.implode("&",$all_code_array).'&HashIV='.$this->all_cfg["HashIV"])));
		}
	}
?>