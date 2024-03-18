<?php

/****************************************************************\ 
 * 广告系统用到的函数
 * 
 * @description  
 * @author Felix 
 * @date 2014年8月27日
 * @encoding UTF-8
 * @copyright Copyright ZAKER
 *  
 * ******* wiki *******
 * 地址：s_wiki_url
 * 
 * ******* 测试步骤 *******
 * 1）xxxxxx
 * 2）xxxxxx
 * 
 * 
 * ******* 重大功能修改历史 *******
 * ==============
 * by 作者 at 日期
 * 功能说明
 * 
 * ==============
 * by 作者 at 日期
 * 功能说明
 * 
\****************************************************************/

//运行环境
define('ZK_ADS_IS_SHOW_ADS', 1);  //所有广告位是否展示广告

if ($_SERVER['SERVER_ADDR']=='121.9.213.58'){
	define('ZK_ADS_DOMAIN', '121.9.213.58/ggs.myzaker.com/');
}elseif($_SERVER['SERVER_NAME']=='bj-dev.myzaker.com'){
	define('ZK_ADS_DOMAIN', 'bj-dev.myzaker.com/ggs.myzaker.com/');
}else{
	define('ZK_ADS_DOMAIN', 'ggs.myzaker.com/');		
}
$GLOBALS['ZK_ads_config'] = require (dirname(__FILE__)."/../config/zk_ads_config.php");

load_helper("ad");

/**
 * 获取配置信息
 * @param 	string $arg 多个将递归获取指定的配置信息 不存在返回null
 * @return 	mixed
 */
if(!function_exists('zk_ads_config')){
	function zk_ads_config(){
	
		$arrArgs = func_get_args();
		
		$_config = $GLOBALS['ZK_ads_config'];
		
		foreach ($arrArgs as $arg) {
			if (isset($_config[$arg])) {
				$_config = $_config[$arg];
			}else{
				$_config = null;
				break;
			}
		}
	
		return $_config;
	}
}

/*---------------------------------------------------------------
 * 取得redis
*---------------------------------------------------------------*/
if(!function_exists('zk_ads_redis')){
	/**
	 * 获取redis实例
	 * @param string $session_name
	 * @return Redis
	 */
	function zk_ads_redis($session_name = 'cache'){

		$config = zk_ads_config('redis_db',$session_name);
		
		if(!isset($config['host'])){
			return array(false,false);
		}
		
		if(!isset($config['timeout'])) $config['timeout'] = 1;
		if(!isset($config['pconnect'])) $config['pconnect'] = FALSE;
		
		$oRedis = db_redis_org($config['host'], $config['port'], $config['timeout'], $config['pconnect'], $config['db']);
		
		$isRedisConnected = $oRedis === FALSE ? FALSE : TRUE;
		
// 		if($isRedisConnected == TRUE && $config['db'] >= 0){
// 			$oRedis->select($config['db']);
// 		}
		
		return array($oRedis, $isRedisConnected);
	}
}



/*---------------------------------------------------------------
 * mongoid分表SHARD KEY
*---------------------------------------------------------------*/
if(!function_exists('zk_ads_mongo_shard_key')){

	function zk_ads_mongo_shard_key($sKey4Shard = NULL, $sInputTableName = '')
	{
		if(!is_null($sKey4Shard)){
			$sKeyMd5 = (string) $sKey4Shard;

			$sTableSubfix = (string) substr($sKeyMd5, 6, 2);

			$sTableName = $sInputTableName. '_'. $sTableSubfix;
			return $sTableName;
		}else{
			return NULL;
		}
	}
}

/*---------------------------------------------------------------
 * MD5、GUID分表SHARD KEY
*---------------------------------------------------------------*/
if(!function_exists('zk_ads_md5_shard_key')){

	function zk_ads_md5_shard_key($sKey4Shard = NULL, $sInputTableName = '')
	{
		if(!is_null($sKey4Shard)){
			$sKeyMd5 = (string) $sKey4Shard;

			$sTableSubfix = (string) substr($sKeyMd5, 0, 2);

			$sTableName = $sInputTableName. '_'. $sTableSubfix;
			return $sTableName;
		}else{
			return NULL;
		}
	}
}


if(!function_exists('zk_ads_add_log')){
	/**
	 * 加入日志数组
	 * @param string $logData
	 * @param string $key
	 */
	function zk_ads_add_log($logData, $key = 'Log'){
		
		if(!isset($GLOBALS['ZK_ads_logs'])){
			$GLOBALS['ZK_ads_logs'] = array();
		}
		
		if(is_array($GLOBALS['ZK_ads_logs']) && !empty($key)){
			$GLOBALS['ZK_ads_logs'][$key][] = $logData;
		}
		
	}
}

if(!function_exists('zk_ads_get_log')){
	/**
	 * 
	 * @param string $format  JSON|HTML|STRING
	 * @return string
	 */
	function zk_ads_get_log($format = 'JSON'){
		
		if(empty($GLOBALS['ZK_ads_logs'])){
			return '';
		}else{
			$var = $GLOBALS['ZK_ads_logs'];
		}
		
		if($format == "JSON"){
			$output = json_encode($var);
		}elseif($format == "HTML"){
			$label = '';
			if (ini_get('html_errors')) {
				$output = print_r($var, true);
				$output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
			} else {
				$output = $label . print_r($var, true);
			}
		}else{
			$output = print_r($var, true);
		}
		
		$GLOBALS['ZK_ads_logs'] = null;
		
		return $output;
	}
}

if(!function_exists('zk_ads_log_file')){

	/**
	 * 写入文件LOG
	 * @param string $sFilePath
	 * @param string $sLog
	 * @param int $nowTime
	 * @param bool $withTime
	 */
	function zk_ads_log_file($sFilePath, $sLog, $nowTime, $withTime = TRUE){
		
		if($withTime == TRUE){
			$nowDate = empty($nowTime) ? date("Y-m-d H:i:s") : date("Y-m-d H:i:s", $nowTime);
			$sLog = $nowDate." : ".$sLog;
		}
		
		file_put_contents($sFilePath, $sLog, FILE_APPEND);
		
	}
}

if(!function_exists('zk_ads_log_firephp')){

	/**
	 * firePHP输出LOG
	 * @param mixed $log
	 */
	function zk_ads_log_firephp($log){

		if(class_exists('FirePHP')){
			FB::Log($log);
		}

	}
}

if(!function_exists('zk_ads_get_def_data')){
	/**
	 * 获取广告资源定义
	 * @param array $wheres
	 * @param array $orderby
	 * @param array $select
	 * @param number $limit
	 * @param number $offset
	 * @return array
	 */
	function zk_ads_get_def_data($wheres = array(), $orderby = array(), $select = array(), $limit = 99999, $offset = 0){

		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_DEF,TRUE);

		if(!empty($select)) 	$db->select($select);
		if(!empty($wheres)) 	$db->where($wheres);
		if(!empty($orderby)) 	$db->order_by($orderby);
		if(!empty($offset)) 	$db->offset($offset);
		if(!empty($limit)) 		$db->limit($limit);
		
		$result = $db->get( ZK_MONGO_TB_ZK_ADS_DEF );

		if(count($result) == 1 && $limit == 1){
			return $result[0];
		}else{
			return $result;
		}

	}
}

if(!function_exists('zk_ads_get_ad_group_def_data')){
	/**
	 * 获取广告组定义
	 * @param array $wheres
	 * @param array $orderby
	 * @param array $select
	 * @param number $limit
	 * @param number $offset
	 * @return array
	 */
	function zk_ads_get_ad_group_def_data($wheres = array(), $orderby = array(), $select = array(), $limit = 99999, $offset = 0){

		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_AD_GROUP_DEF,TRUE);

		if(!empty($select)) 	$db->select($select);
		if(!empty($wheres)) 	$db->where($wheres);
		if(!empty($orderby)) 	$db->order_by($orderby);
		if(!empty($offset)) 	$db->offset($offset);
		if(!empty($limit)) 		$db->limit($limit);

		$result = $db->get( ZK_MONGO_TB_ZK_AD_GROUP_DEF );

		if(count($result) == 1 && $limit == 1){
			return $result[0];
		}else{
			return $result;
		}

	}
}


if(!function_exists('zk_ads_get_user_fav_types')){
	/**
	 * 获取udid喜爱的大类
	 * @param string $_udid
	 * @param string $channelId 渠道ID，ZAKER的ID为'default'
	 */
	function zk_ads_get_user_fav_types($_udid, $channelId=''){
		
		$arrUserFavTypes = null;
		
		$sUserGender = null;
		
		$arrUserFavTypeIds = array();
		
		$arrUserFavTypeWeight = array();
		
		if(!empty($channelId) && $channelId != 'default'){
			//外部渠道的请求不取缓存
			return array($arrUserFavTypeIds, $arrUserFavTypeWeight, $sUserGender);
		}

		$sum = 0;
		
		$is_zaker_user = true;
		if(strpos("_".$_udid, "zk_ads_") > 0){	//判断cookie用户
			$is_zaker_user = false;
		}
		
		if(!empty($_udid) && $is_zaker_user){
			
			list($oRedis, $isRedisConnected) = zk_ads_redis('user_fav');
			
			if(TRUE == $isRedisConnected){
				try {
					$arrUserCache = $oRedis->mget(array(ZK_ADS_CACHE_USER_FAV_TYPE. $_udid,ZK_ADS_CACHE_USER_GENDER. $_udid));
					$arrUserFavTypes = unserialize($arrUserCache[0]);
					$sUserGender = strval($arrUserCache[1]);
				} catch (Exception $e) {
					
				}
				
			}else{
				return array($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender);
			}
			
			if(!is_array($arrUserFavTypes)){
				
				$arrQueue = array(
						'_udid' => $_udid. '',
				);
					
				zk_ads_queue_lpush(ZK_ADS_QUEUE_PRELOAD_USER_FAV_TYPES, $arrQueue);
				
			
// 				$oMongodbUserAppRank 	= db_mongoDB_conn(ZK_MONGO_TB_USER_APP_RANK, TRUE);
// 				$arrWhere 				= array('_udid' => $_udid. '');
// 				$arrUserAppRank 		= $oMongodbUserAppRank->select(array('favour_type'))->where($arrWhere)->getOne(ZK_MONGO_TB_USER_APP_RANK);
// 				$arrUserFavTypes = $arrUserAppRank['favour_type'];
				
// 				if(empty($arrUserFavTypes)){
// 					$arrUserFavTypes = array();
// 				}
				
// 				if(is_array($arrUserFavTypes) && $isRedisConnected){
					try {
						$oRedis->set(ZK_ADS_CACHE_USER_FAV_TYPE. $_udid, serialize(array()));
						$oRedis->setTimeout(ZK_ADS_CACHE_USER_FAV_TYPE. $_udid, 3600*1);
					} catch (Exception $e) {
							
					}
// 				}
			}
			
			if(is_array($arrUserFavTypes) && count($arrUserFavTypes) > 0){
				
			}else{
				return array($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender);
			}
			
			//过滤id>=1000的分类
			$arrUserFavTypesTmp = array();
			foreach ($arrUserFavTypes as $k => $v ){
				if($k >= 1000){
					continue;
				}
				$arrUserFavTypesTmp[$k] = $v;
			}
			$arrUserFavTypes = $arrUserFavTypesTmp;
			
			
			$sum = array_sum($arrUserFavTypes);
			
			foreach ($arrUserFavTypes as $k => $v ){
				if($k >= 1000){
					continue;
				}
				array_push($arrUserFavTypeIds, $k);
// 				$arrUserFavTypeWeight[$k] = round( $v/$sum , 3);
				$arrUserFavTypeWeight[$k] = $v;
			}
		
		}
		
		return array($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender);
		
	}
}


if(!function_exists('zk_ads_get_user_tags')){
	/**
	 * 获取udid的标签
	 * @param string $_udid
	 */
	function zk_ads_get_user_tags($_udid){

		$arrUserTagsStat = null;

		$arrUserTags = array();

		$arrUserTagWeight = array();

		$sum = 0;
		
		$is_zaker_user = true;
		if(strpos("_".$_udid, "zk_ads_") > 0){	//判断cookie用户
			$is_zaker_user = false;
		}
		
		if(!empty($_udid) && $is_zaker_user){
		
			list($oRedis, $isRedisConnected) = zk_ads_redis('user_tag');
			
			if(TRUE == $isRedisConnected){
				try {
					$cacheKey =  md5(ZK_ADS_CACHE_USER_DSP_TAG_STAT. $_udid);
					$arrUserTagsStat = $oRedis->get($cacheKey);
					$arrUserTagsStat = unserialize(zk_gzdecode($arrUserTagsStat));
				} catch (Exception $e) {
				
				}
			}else{
				return array($arrUserTags,$arrUserTagWeight);
			}

			if(!is_array($arrUserTagsStat) || empty($arrUserTagsStat)){
				return array($arrUserTags,$arrUserTagWeight);
			}
							
			foreach ($arrUserTagsStat as $oneTag){
				$k = $oneTag['tag'];
				$v = floatval($oneTag['score']);
				array_push($arrUserTags, $k);
// 				$arrUserTagWeight[$k] = round( $v/$sum , 3);
				$arrUserTagWeight[$k] = $v;
			}
		
		}
		
		return array($arrUserTags,$arrUserTagWeight);
	}
}

if(!function_exists('zk_ads_format_article_jingcai')){
	/**
	 * 输出精彩推荐广告格式
	 * @param unknown $ads
	 * @return array
	 */
	function zk_ads_format_article_jingcai($ads,$oReq){
		
		/**
		 	end_time: "1411660800",
            is_top: "Y",
            pk: "0187660cb4358f9a2f0aeefb8a5f34ef",
            rec_type: "0",
            start_time: "1411523400",
            status: "1",
            title: "ZAKER邀你看《亲爱的》",
            type: "web",
            web: {
              url: "http://huodong.myzaker.com/main/interactive/puzzlegame/lucky.php?id=5421310277a324301d000004",
              type: "",
              need_user_info: "N"
            },
            id: "5421480d9490cb452d0000b7"
		 */
		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);
		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq); //点击统计页
		}
		
		$arrAds = array();
		$arrAds['rec_type'] = "2";
		$arrAds['start_time'] = time();
		$arrAds['end_time'] = $arrAds['start_time'] + 1200;
		$arrAds['pk'] = !empty($ads['zk_pk']) ? strval($ads['zk_pk']) : strval($ads['_id']);
		$arrAds['id'] = !empty($ads['zk_pk']) ? strval($ads['zk_pk']) : strval($ads['_id']);
		$arrAds['status'] = "1";
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['thumbnail_title'] = $arrAds['title'];
        $arrAds['author_name'] = preg_replace('/\(.*\)|（.*）/i', '', $ads['sponsor']);//广告主名称
		$arrAds['keep'] = "Y";

		if($oReq['_version'] >= 6.1){
			$arrAds['stat_read_url'] = zk_ads_format_ad_show_url($ads,$oReq);
			if(!empty($ad_stat_url)){
				$arrAds['ads_stat_url'] = $ad_stat_url;
			}
		}
		if($oReq['_version'] >= 7.94){
			//7.9.4及之后版本需要支持多个数据统计地址，曝光、点击统计url改成数组。
			unset($arrAds['stat_read_url'], $arrAds['ads_stat_url']);
			$arrAds['special_info']['dsp_stat_info']['show_stat_urls'] = zk_ads_get_show_stat_urls($ads, $oReq);
			$arrAds['special_info']['dsp_stat_info']['click_stat_urls'] = zk_ads_get_click_stat_urls($ads, $oReq);
		}
		if($oReq['_version'] >= 8.03){
			$arrAds['is_ad'] = "Y";
		}

		$arrAds['web'] = array(
			'url' => ($oReq['_version'] >= 7.94) ? $ads['ads_link_url'] : $ad_link_url,
			'type' => $ads['web_target'] == 'safari' ? '_blank':'',
			'need_user_info' => ($oReq['_version'] >= 7.94) ? "N" : "Y",
		);
		if (!empty($ads['loading_text']) && $ads['web_target'] != 'safari'){
			$arrAds['web']['loading_text'] = $ads['loading_text'];
		}

		$arrAds['type'] = "web";

		if(!empty($ads['zk_pk'])) {
			//积分商城等文案类型的“广告”
			load_helper('zkcmd');
			$ads['ads_link_url'] = zkopen_article($ads['zk_pk']);
		}
		
		$image_width    = 46;
		$image_height   = 26;

		//精彩推荐只出图文格式（短视频除外）
		if($ads['ads_type'] == 2 || $_REQUEST['video']){
		    $adPicUrl = $ads['ads_short_pic'] ? : $ads['ads_pic'];
            $iamgeSizeMap = array(
                '1' => array('w' => '640', 'h' => '360')
            );
            $arrAds['thumbnail_medias'] = array(
                array(
                    'type' => "image",
                    'url' => $adPicUrl,
                    'm_url' => $adPicUrl,
                    'raw_url' => $adPicUrl,
                    'w' => $iamgeSizeMap[$ads['ads_type']]['w'] ? : '330',
                    'h' => $iamgeSizeMap[$ads['ads_type']]['h'] ? : '220',
                )
            );
            $arrAds['special_info']['item_type'] = '1';
        }
		
		if(isset($oReq['_appid']) && 'iphone' == $oReq['_appid']){
			$image_width    = intval($image_width * 13/$image_height);
			$image_height   =   13;
		}

		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		if(in_array($ads['aid'], $advertiserWithoutAdTag)){
			$arrAds['tag_info'] = array();
		}else{
			$arrAds['tag_info'] = array(
                'image_url'     => $ads['ads_type'] == 2 ? 'http://zkres3.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216' : 'http://zkres3.myzaker.com/data/image/mark/ad_2x.png?v=2015061216',
                'image_height'  => $image_height,
				'image_width'   => $image_width
        	);
		}

		//如果广告落地页是app内部地址，则需要按app内部格式拼装
		if(is_app_inner_url($ads['ads_link_url'])){
			zk_ads_format_app_inner_content_for_jingcai($arrAds, $ads['ads_link_url'], $oReq);
		}

		return $arrAds;
		
	}
	
}

if(!function_exists('zk_ads_format_wap_jingcai')){
	/**
	 * 输出wap精彩推荐广告格式
	 * @param unknown $ads
	 * @return array
	 */
	function zk_ads_format_wap_jingcai($ads,$oReq){

		/**
		  	img: "http://zkres3.myzaker.com/201503/5445b6f91bc8e0e47b8b4580_100.jpg",
      		title: "这些礼物千万不能送人",
      		id: "5508f83f9490cb5b15000070"
      		open_type : "web",
      		web_url: ""
		 */
		$randColor = array("#ff6363","#fead7c","#89c46c","#81c4ee");

		$arrAds = array();
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['_id']=$arrAds['id'] = strval($ads['_id']);
		$arrAds['open_type'] = "web";
		$arrAds['is_top'] = "Y";

		$arrAds['web_url'] = $ads['ads_link_url'];
		$arrAds['dsp_stat_info']['show_stat_urls'] = zk_ads_get_show_stat_urls($ads, $oReq);
		$arrAds['dsp_stat_info']['click_stat_urls'] = zk_ads_get_click_stat_urls($ads, $oReq);

		//小图+文字
		if( !empty($ads['ads_short_pic'])){
			$arrAds['thumbnail_medias'] = array(
				array(
					'type' => "image",
					'url' => $ads['ads_short_pic']
				)
			);
			$arrAds['item_type'] = "1";
		}
		//大图
		elseif($ads['ads_type'] == 1 && !empty($ads['ads_pic'])){
			$arrAds['thumbnail_medias'] = array(
				array(
					'type' => "image",
					'url' => $ads['ads_pic']
				)
			);
			//大图，标签内嵌
			$arrAds['item_type'] = !empty($ads['ads_content'])? "1_b": "1_f";
		}
		//三图
		elseif($ads['ads_type'] == 15){ //多图并列广告样式
			foreach ($ads['multi_pics'] as $pic) {
				$arrAds['thumbnail_medias'][] = array(
					'type' => "image",
					'url' => $pic
				);
			}
			$arrAds['item_type'] = '3_b';
		}
		//纯文字用第一个文字代替图片
		elseif ($ads['ads_type'] == 3) {
			$arrAds["cover"]['color'] = $randColor[(int)rand(0,3)];

			//过滤非中文字符
			$preg = "/[\x{4e00}-\x{9fa5}]+/u";
			preg_match($preg,$arrAds['title'],$tmp_title);

			$arrAds["cover"]['font'] = mb_substr($tmp_title[0],0,1,'utf-8');
			$arrAds["item_type"] = '1';
		}

		//大图类型的广告tag不一样
		$arrAds['tag_image'] = in_array($arrAds['item_type'], array('1_b', '1_f')) ?
			'http://zkres.myzaker.com/data/image/mark2/ad_gray_2x.png' :
			'http://zkres3.myzaker.com/data/image/mark/ad_2x.png?v=2015061216';

		return $arrAds;

	}

}

/**
 * wap信息流位置的广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_format_wap_news_feed')){
	function zk_ads_format_wap_news_feed($ads,$oReq){
		
		$arrAds['show_type'] = $ads['ads_type'];
		$arrAds['id'] = strval($ads['_id']);
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['pic'] = strval($ads['ads_pic']);
		$arrAds['short_pic'] = strval($ads['ads_short_pic']);
		$arrAds['multi_pics'] = $ads['multi_pics']? $ads['multi_pics']: array();
		$arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
		$arrAds['read_url'] = zk_ads_format_ad_show_url($ads,$oReq);

		$tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
		$arrAds['tag_pic'] = array(
			'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
			'image_height'  => 26,
			'image_width'   => 46
		);
		return $arrAds;
	}
}

if(!function_exists('zk_ads_format_article_block')){
	/**
	 * 输出文章列表广告格式
	 * @param unknown $ads
	 * @param unknown $oReq
	 * @return array
	 */
	function zk_ads_format_article_block($ads,$oReq){

		$web_url = adMonitorUrlFormatter::MakeAdUrl($ads['ads_link_url'], $oReq);
		
		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);//落地页
		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq); //点击统计页
		}
		
		$arrAds = array();
		$arrAds['pk'] = !empty($ads['zk_pk']) ? strval($ads['zk_pk']) : strval($ads['_id']);//zk_pk为内部文章地址
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['thumbnail_title'] = $arrAds['title'];
		$arrAds['date'] = "";
		$arrAds['auther_name'] = preg_replace('/\(.*\)|（.*）/i', '', $ads['sponsor']);//广告主名称
		
		if($ads['deliver_type'] == 2){
			$arrAds['thumbnail_pic'] = $ads['ads_pic'];
			$arrAds['thumbnail_mpic'] = $ads['ads_pic'];
			$arrAds['thumbnail_min_pic'] = $ads['ads_pic'];
			$arrAds['thumbnail_picsize'] = "1242,700";
		}
		
		$arrAds['is_full'] = "NO";
		$arrAds['full_arg'] = "_appid";

		if(empty($ads['zk_pk'])) {
			$arrAds['weburl'] = $web_url;
			$arrAds['type'] = "web2";
		}


		$porscheAds = array('57ca2e9eb09efe264d000000','57ca2f8eb09efe6e4d000000'); //保时捷广告
		if(in_array($ads['ad_group_id'], $porscheAds)){
			$tag_position = 2;
		}else{
			$tag_position = 1;
		}

		$arrAds['special_info'] = array(
				'open_type' => "web",
				'need_user_info' => "Y",
				'web_url' => $ad_link_url,
				'icon_url' => "http://zkres3.myzaker.com/data/image/mark2/ad_2x.png",
				'show_jingcai' => "Y",
				'tag_position' => $tag_position,
		);

		if($_REQUEST['_version'] >= 6.1 || ($oReq['_appid'] == 'ipad' && $oReq['_version'] >= 3.3)){
			$arrAds['special_info']['stat_read_url'] = zk_ads_format_ad_show_url($ads,$oReq);
			if(!empty($ad_stat_url)){
				$arrAds['special_info']['stat_click_url'] = $ad_stat_url;
			}
			
		}
		if($oReq['_appid'] == 'iphone' && $oReq['_version'] > 6.6){
			$is_itunes_url = (int)zk_ads_is_itunes_url($ads['ads_link_url']);
			$arrAds['special_info']['is_itunes_url'] = $is_itunes_url;
			if($is_itunes_url){
				$arrAds['special_info']['web_url'] = $ads['ads_link_url'];
				$arrAds['special_info']['stat_click_url'] = zk_ads_format_ad_stat_url($ads,$oReq);
			}
		}

		if($oReq['_version'] >= 7.94){
			//7.9.4及之后版本需要支持多个数据统计地址，曝光、点击统计url改成数组。
			unset($arrAds['special_info']['stat_read_url'], $arrAds['special_info']['stat_click_url']);
			$arrAds['special_info']['need_user_info'] = 'N';
			$arrAds['special_info']['web_url'] = $web_url;
			$arrAds['special_info']['dsp_stat_info']['show_stat_urls'] = zk_ads_get_show_stat_urls($ads, $oReq);
			$arrAds['special_info']['dsp_stat_info']['click_stat_urls'] = zk_ads_get_click_stat_urls($ads, $oReq);
		}

// 		$arrAds['page'] = rand(5, 6);
// 		$arrAds['index'] = rand(3, 5);
		$arrAds['special_type'] = "tag";

		if(!empty($ads['zk_pk'])){
			//积分商城等文案类型的“广告”
			load_helper('zkcmd');
			$ads['ads_link_url'] = zkopen_article($ads['zk_pk']);
		}else{
			$arrAds['full_url'] = "";
		}

		if(empty($oReq['integrated_channel'])){
			$arrAds['ga_info'] = array(
					'category' => "AD",
					'action' => "Article"
			);
		}
		
		$arrAds['is_ad'] = "Y";
		
		//打开外部浏览器
		if($ads['web_target'] == 'safari'){
			$arrAds['type'] = "other";
			$arrAds['special_info']['open_type'] = 'safari';
		}elseif (!empty($ads['loading_text'])){
			$arrAds['special_info']['web_show_arg']['loading_text'] = $ads['loading_text'];
		}
		
		if($ads['deliver_type'] == 2){
			$arrAds['thumbnail_medias'] = array(
					array(
							'type' => "image",
							'url' => $ads['ads_pic'],
							'm_url' => $ads['ads_pic'],
							'raw_url' => $ads['ads_pic'],
							'w' => '1242',
							'h' => '700',
					)
			);
			$arrAds['title'] = $arrAds['thumbnail_title'] = "";
			if($oReq['_appid'] == "iphone" && $oReq['_version'] == 8.36 && $_REQUEST['need_app_integration'] == 1){
				$arrAds['special_info']['item_type'] = "1_b";  //广告标签在图外左上位置
			}else{
				$arrAds['special_info']['item_type'] = "1_f";  //广告标签在图里右上角
			}
				
			$pathInfo = pathinfo($ads['ads_pic']);
			if($pathInfo['extension'] == "gif"){   //gif类型图片
				$arrAds['thumbnail_gif_pic'] = $ads['ads_pic'];
				$arrAds['thumbnail_medias'][0]['gif_url'] = $ads['ads_pic'];
			}

		}elseif($ads['deliver_type'] == 1 && $ads['ads_type'] == 2){
			$arrAds['thumbnail_medias'] = array(
				array(
					'type' => "image",
					'url' => $ads['ads_short_pic'],
					'm_url' => $ads['ads_short_pic'],
					'raw_url' => $ads['ads_short_pic'],
					'w' => '330',
					'h' => '220',
				)
			);
			$arrAds['special_info']['item_type'] = '1';

		}elseif($ads['deliver_type'] == 1 && $ads['ads_type'] == 1){
			$arrAds['thumbnail_medias'] = array(
				array(
					'type' => "image",
					'url' => $ads['ads_pic'],
					'm_url' => $ads['ads_pic'],
					'raw_url' => $ads['ads_pic'],
					'w' => '640',
					'h' => '360',
				)
			);
			if($oReq['_appid'] == "iphone" && $oReq['_version'] == 8.36 && $_REQUEST['need_app_integration'] == 1){
				$arrAds['thumbnail_title'] = "";
				$arrAds['special_info']['item_type'] = "1_b";  //广告标签在图外左上位置
			}else{
				$arrAds['special_info']['item_type'] = !empty($arrAds['title']) ? '1_b' : '1_f';
			}
		
		}elseif($ads['ads_type'] == 15){ //多图并列广告样式

			if($_REQUEST['need_app_integration'] == 1){ //竖排集成频道（如房地产）
				foreach ($ads['multi_pics'] as $pic) {
					$arrAds['thumbnail_medias'][] = array(
						'type' => "image",
						'url' => $pic,
						'm_url' => $pic,
						'raw_url' => $pic,
						'w' => '330',
						'h' => '220',
					);
				}
				$arrAds['special_info']['item_type'] = '3_b';
			}
		}

		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		if(in_array($ads['aid'], $advertiserWithoutAdTag)){
			$arrAds['special_info']['icon_url'] = '';
		}

		//如果广告落地页是app内部地址，则需要按app内部格式拼装
		if(is_app_inner_url($ads['ads_link_url'])){
			zk_ads_format_app_inner_content($arrAds, $ads['ads_link_url'], $oReq);
		}
		
		return $arrAds;

	}

}

if(!function_exists('zk_ads_format_article_recommend')){
	/**
	 * 输出推荐文章列表广告格式
	 * @param unknown $ads
	 * @param unknown $oReq
	 * @return array
	 */
	function zk_ads_format_article_recommend($ads,$oReq){

	    $web_url = adMonitorUrlFormatter::MakeAdUrl($ads['ads_link_url'], $oReq);

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);
		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq); //点击统计页
		}

		$arrAds = array();
		$arrAds['pk'] = strval($ads['_id']);
		$arrAds['app_ids'] = strval("400000");
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['thumbnail_title'] = $arrAds['title'];
		$arrAds['date'] = "";
		$arrAds['auther_name'] = preg_replace('/\(.*\)|（.*）/i', '', $ads['sponsor']);//广告主名称
		
		$arrAds['is_full'] = "NO";

		if(empty($ads['zk_pk'])) {
			$arrAds['weburl'] = $web_url;
			$arrAds['type'] = "web2";
		}


		$arrAds['special_info'] = array(
				'open_type' => "web",
				'need_user_info' => "Y",
				'web_url' => $ad_link_url,
				'icon_url' => "http://zkres3.myzaker.com/data/image/mark2/ad_2x.png",
				'show_jingcai' => "Y",
		);

		if($_REQUEST['_version'] >= 6.1 ){
			$arrAds['special_info']['stat_read_url'] = zk_ads_format_ad_show_url($ads,$oReq);
			if(!empty($ad_stat_url)){
				$arrAds['special_info']['stat_click_url'] = $ad_stat_url;
			}
		}
		if($oReq['_appid'] == 'iphone' && $oReq['_version'] > 6.6){
			$is_itunes_url = (int)zk_ads_is_itunes_url($ads['ads_link_url']);
			$arrAds['special_info']['is_itunes_url'] = $is_itunes_url;
			if($is_itunes_url){
				$arrAds['special_info']['web_url'] = $ads['ads_link_url'];
				$arrAds['special_info']['stat_click_url'] = zk_ads_format_ad_stat_url($ads,$oReq);
			}
		}

		if($oReq['_version'] >= 7.94){
			//7.9.4及之后版本需要支持多个数据统计地址，曝光、点击统计url改成数组。
			unset($arrAds['special_info']['stat_read_url'], $arrAds['special_info']['stat_click_url']);
			$arrAds['special_info']['need_user_info'] = 'N';
			$arrAds['special_info']['web_url'] = $web_url;
			$arrAds['special_info']['dsp_stat_info']['show_stat_urls'] = zk_ads_get_show_stat_urls($ads, $oReq);
			$arrAds['special_info']['dsp_stat_info']['click_stat_urls'] = zk_ads_get_click_stat_urls($ads, $oReq);
		}
		if($oReq['_version'] >= 8.03){
			$arrAds['is_ad'] = "Y";
		}

		if(!empty($ads['zk_pk'])){
			//积分商城等文案类型的“广告”
			load_helper('zkcmd');
			$ads['ads_link_url'] = zkopen_article($ads['zk_pk']);
		}else{
			$arrAds['full_url'] = "";
		}

        if( !empty($ads['ads_short_pic'])){
			$arrAds['thumbnail_medias'] = array(
					array(
							'type' => "image",
							'url' => $ads['ads_short_pic'],
							'm_url' => $ads['ads_short_pic'],
							'raw_url' => $ads['ads_short_pic'],
					)
			);
			$arrAds['special_info']['item_type'] = "1";
		}
		elseif($ads['ads_type'] == 1 && !empty($ads['ads_pic'])){
			$arrAds['thumbnail_medias'] = array(
					array(
							'type' => "image",
							'url' => $ads['ads_pic'],
							'm_url' => $ads['ads_pic'],
							'raw_url' => $ads['ads_pic'],
					)
			);

			$arrAds['thumbnail_title'] = "";
			if($oReq['_appid'] == "iphone" && $oReq['_version'] == 8.36){
				$arrAds['special_info']['item_type'] = "1_q";  //广告标签在图外左上位置
			}else{
				$arrAds['special_info']['item_type'] = "1_f";  //广告标签在图里右上角
			}
			$arrAds['special_info']['icon_url'] = "http://zkres3.myzaker.com/data/image/mark/ad_2x.png";

			$pathInfo = pathinfo($ads['ads_pic']);
			if($pathInfo['extension'] == "gif"){   //gif类型图片
				$arrAds['thumbnail_gif_pic'] = $ads['ads_pic'];
				$arrAds['thumbnail_medias'][0]['gif_url'] = $ads['ads_pic'];
			}
		}
		elseif($ads['ads_type'] == 15){ //多图并列广告样式
			foreach ($ads['multi_pics'] as $pic) {
				$arrAds['thumbnail_medias'][] = array(
					'type' => "image",
					'url' => $pic,
					'm_url' => $pic,
					'raw_url' => $pic,
				);
			}
			$arrAds['special_info']['item_type'] = '3_b';
		}

		//打开外部浏览器
		if($ads['web_target'] == 'safari'){
			$arrAds['type'] = "other";
			$arrAds['special_info']['open_type'] = 'safari';
		}
		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		if(in_array($ads['aid'], $advertiserWithoutAdTag)){
			$arrAds['special_info']['icon_url'] = '';
		}

		//如果广告落地页是app内部地址，则需要按app内部格式拼装
		if(is_app_inner_url($ads['ads_link_url'])){
			zk_ads_format_app_inner_content($arrAds, $ads['ads_link_url'], $oReq);
		}
		
		return $arrAds;

	}

}

/**
 * 输出封面广告格式
 * @param array $ads  广告信息
 * @param array $oReq 请求信息
 * @param int $rank  广告排序
 * @return array
 */
if(!function_exists('zk_ads_format_app_cover')){
	function zk_ads_format_app_cover($ads, $oReq, $rank=1){
		$playOrder = zk_ads_get_cover_ad_rank($ads['_id'], $rank);
		//频道专有广告
		$adInfo = array(
			'pk' => strval($ads['_id']),
			'title' => $ads['ads_name'],
			'show_num' => 10,
			'click_num' => 10,
			'start_date' => date('Y-m-d H:i', $ads['start_time']),
			'end_date' => date('Y-m-d H:i', $ads['end_time']),
			'pic_v'	=> array(
				'w' => 1242,
				'h' => 2208,
				'url' => $ads['ads_pic'],
			),
			'play_order' => array(
				'is_duzhan' => $playOrder['is_duzhan'],
				'play_rank' => $playOrder['play_rank'],
			),
			'show_stat_urls' => zk_ads_get_show_stat_urls($ads, $oReq),
			'click_stat_urls' => zk_ads_get_click_stat_urls($ads, $oReq),
		);
		if(!empty($ads['ads_link_url'])){
			$adInfo['special_info'] = array(
				'open_type' => "web",
				'need_user_info' => "N",
				'web_url' => $ads['ads_link_url'],
			);
		}

		return $adInfo;
	}
}

if(!function_exists('zk_ads_format_ads_type_video')) {
	function zk_ads_format_ads_type_video($ads, $oReq) {

		$ad_pic_url = zk_ads_fix_ad_img_url($ads['ads_pic']);
		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);
		$ad_video_url = $ads['video_url'];
		$ad_video_cover = $ads['video_cover'];

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ads_content = '<div style="border: 1px solid rgba(0,0,0,0.1);border-top:none;text-align:left;padding-left:12px;padding-top:8px;padding-bottom:8px;font-size:12px;border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;"><a onclick="ggs_click();"  href="'.$ad_link_url.'" style="text-decoration: none;color: #000;border: none;">进入详情页</a></div>';


		$js_temp = '           
                   var ggs_play = function(){
                     var bImg = document.createElement("img");
                     bImg.src="{{PLAY_STAT_URL}}";
                  }

                  var write_ad = function(){
                     var pucl = document.getElementById("article_bottom");
                     if(pucl != null){
                        var ad_html = \'<div style="margin:auto;padding:0px;text-align:center;width:100%;"><div style="position: relative;"><div style="position: relative;font-size: 10px;color: #ababab;text-align: center;height: 25px;"></div><img style="width:23px;height:13px;position: absolute;top:40px;right: 0px;" src="http://zkres3.myzaker.com/data/image/mark/ad_2x.png?v=2015061216"><div id="video-banner"><div style="height:32px;width:100%;position:absolute;bottom: 0px;background-color: #000;filter:alpha(Opacity=30);-moz-opacity:0.3;opacity: 0.3;"><div style="height: 24px;width:32px;padding-top:4px;float: right;padding-right:10px;"><img style="height:24px;" src="http://zkres3.myzaker.com/data/image/mark/video-camera.svg?v=2015061216" /></div></div><img id="video_cover" style="display:block;border-top-left-radius: 6px; border-top-right-radius: 6px;margin:0px;padding:0;"  src="{{PHPCODE2}}" /></div><video poster="{{PHPCODE2}}" style="display:none;border-top-left-radius: 6px; border-top-right-radius: 6px;" id="video_player" width="100%" height="auto"  preload="auto" webkit-playsinline playsinline src="{{PHPCODE3}}"></video></div>{{AD_TITLE}}</div><div style="height:0px"></div><div id="ad_img_wrapper"></div>\';
                               pucl.innerHTML=ad_html;
                     }
                  }

                  var stop_ad_video = function(){
                      document.getElementById("video_player").pause();
                  }

                  //if(typeof ad_init == \'undefined\'){
                  // var ad_init = true;
                     write_ad();
                  //}
                  
                  	// 将统计img插入进行广告统计
					var ad_count = function(cb) {
						var ad_img_wrapper = document.getElementById(\'ad_img_wrapper\');
						var ad_html = \'{{THIRD_VIEW_IMG}}\';
						ad_img_wrapper.innerHTML = ad_html;
						cb && cb();
					}

                   var video_cover = document.getElementById("video_cover");
                              video_cover.addEventListener("click", function(){
                                  ggs_play();
                            var video = document.getElementById("video_player");
                            video.style.display="block";
                            this.style.display="none";
                            document.getElementById("video-banner").style.display="none";
                            video.play();
                        }, false);

                        var video_player = document.getElementById("video_player");
                        video_player.addEventListener("click", function(){
                            this.paused? this.play() : this.pause()
                        }, false);
               ';


		$js_temp = str_replace('{{PHPCODE2}}', $ad_video_cover, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_video_url, $js_temp);
		$js_temp = str_replace('{{CLICK_STAT_URL}}', $ad_stat_url, $js_temp);


		$js_temp = str_replace('{{AD_TITLE}}',$ads_content,$js_temp);
		$js_temp = str_replace('{{LINK_URL}}',$ad_link_url,$js_temp);


		//----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		$oReq['ads_id']=(string)$ads['_id'];
		$oReq['creative_id']=(string)$ads['creativeid'];
		$show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
		$show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$ad_play_url = 'http://'.ZK_ADS_DOMAIN.'zk_ggs_play.php?'.http_build_query($oReq);//播放统计链接

		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);
		$js_temp = str_replace('{{PLAY_STAT_URL}}', $ad_play_url, $js_temp);

		return $js_temp;
	}
}


if(!function_exists('zk_ads_format_ads_type_1')){
	/**
	 * 输出“大图+文字”广告html样式
	 */
	function zk_ads_format_ads_type_1($ads, $oReq){

		if(!empty($ads['video_url'])&&!empty($ads['video_cover'])){
			return zk_ads_format_ads_type_video($ads, $oReq);
			exit;
		}

		$ad_pic_url = zk_ads_fix_ad_img_url($ads['ads_pic']);

		$ad_link_url = zk_ads_get_ad_target_url($ads, $oReq);
		$show_stat_img = zk_ads_format_show_stat_img($ads, $oReq);
		$click_stat_img = zk_ads_format_click_stat_img($ads, $oReq);


		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		$no_ad_tag = in_array($ads['aid'], $advertiserWithoutAdTag) ? 1 : 0;
		if($no_ad_tag){
			$ad_tag = "";
		}else{
			$ad_tag = '<img id="dsp_ad" class="gg_title_mark2 mark_disable" src="http://zkres3.myzaker.com/data/image/mark/ad_2x.png?v=2015061216">';
		}

		$no_title = $ads['ads_content'] == '' ? 1 : 0;

		$js_temp='
				var ggs_click = function(){
					{{CLICK_STAT_IMG}}
				}
				
				var write_ad = function() {
					var pucl = document.getElementById("article_bottom");
		
					var _no_title = {{VAR_NO_TITLE}}; 
					var _no_ad_tag = {{VAR_NO_AD_TAG}};
					var _version = {{VAR_VERSION}};
					var ads_group = "{{VAR_ADS_GROUP}}";
						
				
					if (pucl != null) {
				
						var styleEle = document.createElement("style");
						
						var baseStyle = "a.gg_wrapper{margin: 0;padding: 0;text-decoration: none;border:none}.gg_wrapper{position: relative;text-decoration:none;border:0;display:block;margin-bottom:16px;-webkit-tap-highlight-color:transparent;-webkit-touch-callout:none;text-decoration:none;border:0;margin:auto;padding:0;display:block}.gg_content{margin:auto;font-size:0;}.gg_title{display:none; -webkit-box-pack: center; -webkit-justify-content: center; -ms-flex-pack: center; justify-content: center; -webkit-box-align: center; -webkit-align-items: center; -ms-flex-align: center; align-items: center; -webkit-align-content: center; -ms-flex-line-pack: center; align-content: center;padding:3.45% 3.5% 3%}.gg_title_main{display:block;font-size:3.6vw;height:4.35vw;line-height: 4.35vw;color:#3b3b3b;width:calc(100% - 3% - 23px);vertical-align:middle;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;-webkit-text-size-adjust: none;}.gg_title_mark{display:none;vertical-align:middle;width:23px;height:4.35vw;margin-left:3%;background-repeat:no-repeat;background-position:50% 20%;background-size:23px 13px;}.gg_title_mark2{width:23px;height:13px;position: absolute; top:22px; right:1px;}.gg_image{border-radius:5px;position:relative;display:block;background-repeat:no-repeat;background-position:center;background-size:cover;width:100%;padding-top:55.93%;}@media screen and (min-width: 640px){.gg_title_main{font-size:17px;line-height: 22px;height:22px}.gg_title_mark{margin-top:5px;height:13px}}";
				
						var borderStyle = "#content #article_bottom a.gg_border{border:1px solid #dfdfdf;border-radius:6px;}";
						var borderRadiusStyle = ".border_radius{border-radius:6px;}";
						var markStyle = ".mark_active{display:block}";
						var titleStyle=".title_active{display: -webkit-box; display: -webkit-flex; display: -ms-flexbox; display: flex;}.mark_disable{display:none}";
						var borderRadiusNotopStyle=".gg_image.border_radius_nobtm{border-bottom-left-radius: 0px; border-bottom-right-radius: 0px; }";
				
						if (_version >= 8.0) {
							baseStyle = baseStyle + borderRadiusStyle;
						}
						if (ads_group == "wap_bottom_banner") {
							baseStyle = baseStyle + borderStyle;
						}
						if(!_no_title){
							baseStyle = baseStyle + titleStyle + borderRadiusNotopStyle;
						}
						if (_no_ad_tag != 1) {
							baseStyle = baseStyle + markStyle;
						}
						
						styleEle.innerHTML = baseStyle;
				
						document.head.appendChild(styleEle);
						
						var ad_html = \'<a class="gg_wrapper gg_border" onclick="ggs_click()" href="{{LINK_URL}}"><div class="ggs_div gg_content border_radius"><div class="gg_image border_radius border_radius_nobtm" style="background-image:url({{PIC_URL}})">{{AD_TAG}}</div><div class="gg_title title_active" ><span class="gg_title_main">{{AD_TITLE}}</span><div class="gg_title_mark mark_active" style="background-image:url(http://zkres3.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216);"></div></div></div></a> <div id="ad_img_wrapper"></div>\';
				
						pucl.innerHTML = ad_html;
					
					}
				
				}
		   
				write_ad();
				
				// 将统计img插入进行广告统计
				var ad_count = function(cb) {
					var ad_img_wrapper = document.getElementById("ad_img_wrapper");
					var ad_html = \'{{SHOW_STAT_IMG}}\';
					ad_img_wrapper.innerHTML = ad_html;
					cb && cb();
				}
		';



		$js_temp = str_replace('{{VAR_NO_TITLE}}', $no_title, $js_temp);
		$js_temp = str_replace('{{VAR_NO_AD_TAG}}', $no_ad_tag, $js_temp);
		$js_temp = str_replace('{{VAR_VERSION}}', $oReq['_version']?$oReq['_version']:0, $js_temp);
		$js_temp = str_replace('{{VAR_ADS_GROUP}}', $oReq['ads_group'], $js_temp);

		$js_temp = str_replace('{{LINK_URL}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{SHOW_STAT_IMG}}', $show_stat_img, $js_temp);
		$js_temp = str_replace('{{CLICK_STAT_IMG}}', $click_stat_img, $js_temp);
		$js_temp = str_replace('{{PIC_URL}}', $ad_pic_url, $js_temp);
		$js_temp = str_replace('{{AD_TITLE}}',$ads['ads_content'],$js_temp);
		$js_temp = str_replace('{{AD_TAG}}', $ad_tag, $js_temp);

		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ads_type_2')){
	/**
	 * 输出“小图+文字”广告html样式
	 */
	function zk_ads_format_ads_type_2($ads, $oReq){

		$ad_short_pic_url = zk_ads_fix_ad_img_url($ads['ads_short_pic']);

		$ad_link_url = zk_ads_get_ad_target_url($ads, $oReq);
		$show_stat_img = zk_ads_format_show_stat_img($ads, $oReq);
		$click_stat_img = zk_ads_format_click_stat_img($ads, $oReq);

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		$no_ad_tag = in_array($ads['aid'], $advertiserWithoutAdTag) ? 1 : 0;


		$js_temp = '
				var ggs_click = function(){
					{{CLICK_STAT_IMG}}
				}
				
				var write_ad = function () {
					var pucl = document.getElementById("article_bottom");
			
					var _no_ad_tag = {{VAR_NO_AD_TAG}};
					var _version = {{VAR_VERSION}};
					var ads_group = "{{VAR_ADS_GROUP}}";
			
					if (pucl != null) {
						var styleEle = document.createElement("style");
			
						var baseStyle = "a.gg_wrapper{margin: 0;padding: 0;text-decoration: none;border:none}.gg_wrapper{text-decoration:none;border:0;display:block;margin-bottom:16px;-webkit-tap-highlight-color:transparent;-webkit-touch-callout:none;text-decoration:none;margin:auto;padding:0;display:block}.gg_content{margin:auto;padding:3.5%;font-size:0;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;-ms-flex-pack:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;-webkit-align-content:center;-ms-flex-line-pack:center;align-content:center}.gg_title{width:calc(100% - 35.25%);display:block;vertical-align:middle}.gg_title_main{font-size:3.6vw;line-height: 5vw;color:#3b3b3b;text-overflow:-o-ellipsis-lastline;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;-webkit-text-size-adjust:none;display:block;}.gg_title_mark{margin-top: 1px;display:none;vertical-align: middle;width:23px;height:4.35vw;background-repeat:no-repeat;background-position:center;background-size:23px 13px;}.gg_image{position:relative;display:block;vertical-align:middle;background-repeat:no-repeat;background-position:center;background-size:cover;width:31%;padding-top:22%;margin-left:4.25%;}.gg_image_after{position: absolute; left: 0; top: 0; width: 100%; height: 100%; border: solid 1px rgba(239, 239, 239, .5); box-sizing: border-box; }@media screen and (min-width: 640px){.gg_title_main{font-size:17px;line-height: 22px}.gg_title_mark{margin-top:5px;height:13px}}";
			
						var borderStyle = "#content #article_bottom a.gg_border{border:1px solid #dfdfdf;border-radius:6px;}";
						var borderRadiusStyle = ".border_radius{border-radius:6px;}";
						var markStyle = ".mark_active{display:block}";
			
						if (_version >= 8.0) {
							baseStyle = baseStyle + borderRadiusStyle;
						}
						if (ads_group == "wap_bottom_banner") {
			
							baseStyle = baseStyle + borderStyle;
						}
						if (_no_ad_tag != 1) {
							baseStyle = baseStyle + markStyle;
						}
						styleEle.innerHTML = baseStyle;
			
						document.head.appendChild(styleEle);
			
			
						if (pucl != null) {
			
							var ad_html =
								\'<a class="gg_wrapper gg_border" onclick="ggs_click()" href="{{LINK_URL}}"><div class="ggs_div gg_content border_radius" ><div class="gg_title"><div class="gg_title_main">{{AD_TITLE}}</div><div class="gg_title_mark mark_active" style="background-image:url(http://zkres3.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216)"></div></div><div class="gg_image border_radius" style="background-image:url({{PIC_URL}})"><div class="gg_image_after border_radius"></div></div></div></a> <div id="ad_img_wrapper"></div> \';
			
							pucl.innerHTML = ad_html;
						}
					}
				}
	
				write_ad();
				
				// 将统计img插入进行广告统计
				var ad_count = function(cb) {
					var ad_img_wrapper = document.getElementById("ad_img_wrapper");
					var ad_html = \'{{SHOW_STAT_IMG}}\';
					ad_img_wrapper.innerHTML = ad_html;
					cb && cb();
				}
		';


		$js_temp = str_replace('{{VAR_NO_AD_TAG}}', $no_ad_tag, $js_temp);
		$js_temp = str_replace('{{VAR_VERSION}}', $oReq['_version']?$oReq['_version']:0, $js_temp);
		$js_temp = str_replace('{{VAR_ADS_GROUP}}', $oReq['ads_group'], $js_temp);

		$js_temp = str_replace('{{LINK_URL}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{SHOW_STAT_IMG}}', $show_stat_img, $js_temp);
		$js_temp = str_replace('{{CLICK_STAT_IMG}}', $click_stat_img, $js_temp);
		$js_temp = str_replace('{{PIC_URL}}', $ad_short_pic_url, $js_temp);
		$js_temp = str_replace('{{AD_TITLE}}', $ad_content, $js_temp);

		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ads_type_3')){
	/**
	 * 纯文字
	 */
	function zk_ads_format_ads_type_3($ads, $oReq){
		
		//$rand = mt_rand(1, 3);
		$rand = mt_rand(1, 2);
		if($rand == 1){
			return zk_ads_format_ads_type_3a($ads, $oReq);
		}elseif($rand == 2){
			return zk_ads_format_ads_type_3c($ads, $oReq);
		}else{
			return zk_ads_format_ads_type_3b($ads, $oReq);
		}
		
	}
}

if(!function_exists('zk_ads_format_ads_type_3a')){
	/**
	 * 纯文字a
	 */
	function zk_ads_format_ads_type_3a($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1b.png) repeat-x,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1a.png) no-repeat,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1a.png) no-repeat right top;background-size:contain;background-clip:content-box,border-box,border-box;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						//if(typeof ad_init == \'undefined\'){
						//	var ad_init = true;
							write_ad();
						//}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
        //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	    //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ads_type_3b')){
	/**
	 * 纯文字b
	 */
	function zk_ads_format_ads_type_3b($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2b.png) repeat-x,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2a.png) no-repeat,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2c.png) no-repeat right top;background-size:contain;background-clip:content-box,border-box,border-box;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						//if(typeof ad_init == \'undefined\'){
						//	var ad_init = true;
							write_ad();
						//}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
       //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	   //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ads_type_3c')){
	/**
	 * 纯文字c
	 */
	function zk_ads_format_ads_type_3c($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp3.png) repeat-x;background-size:contain;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						//if(typeof ad_init == \'undefined\'){
						//	var ad_init = true;
							write_ad();
						//}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
          //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	   //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ads_type_15')){
	/**
	 * 3图样式
	 */
	function zk_ads_format_ads_type_15($ads, $oReq){
		if(!is_array($ads['multi_pics']) || count($ads['multi_pics']) < 3){
			exit;
		}

		$ad_link_url = zk_ads_get_ad_target_url($ads, $oReq);
		$show_stat_img = zk_ads_format_show_stat_img($ads, $oReq);
		$click_stat_img = zk_ads_format_click_stat_img($ads, $oReq);

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';
		$ad_pic1 = $ads['multi_pics'][0] ? zk_ads_fix_ad_img_url($ads['multi_pics'][0]) : '';
		$ad_pic2 = $ads['multi_pics'][1] ? zk_ads_fix_ad_img_url($ads['multi_pics'][1]) : '';
		$ad_pic3 = $ads['multi_pics'][2] ? zk_ads_fix_ad_img_url($ads['multi_pics'][2]) : '';

		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		$no_ad_tag= in_array($ads['aid'], $advertiserWithoutAdTag) ? 1 : 0;

		$js_temp = '
			var ggs_click = function(){     
				{{CLICK_STAT_IMG}}
			}
			var write_ad = function () {
				var pucl = document.getElementById("article_bottom");
			
				var _no_ad_tag = {{VAR_NO_AD_TAG}};
				var _version = {{VAR_VERSION}};
				var ads_group = "{{VAR_ADS_GROUP}}";
			
				if (pucl != null) {
					var styleEle = document.createElement("style");
			
					var baseStyle="a.gg_wrapper{margin: 0;padding: 0;text-decoration: none;border:none}.gg_wrapper{position:relative;text-decoration:none;border:0;display:block;margin-bottom:16px;-webkit-tap-highlight-color:transparent;-webkit-touch-callout:none}.gg_content{display:block;width:auto;padding:3.5% 3.5% 3.75%}.gg_title{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-pack:center;-webkit-justify-content:center;-ms-flex-pack:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;-webkit-align-content:center;-ms-flex-line-pack:center;align-content:center;overflow:hidden;margin-bottom: 2.3%;}.gg_title_main{display:block;width:calc(100% - 3% - 23px);font-size:3.6vw;height:4.35vw;line-height: 5.2vw;color:#3b3b3b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;-webkit-text-size-adjust: none;flex:1}.gg_title_mark{flex:0 0 30px;display:none;vertical-align:middle;width:23px;height:4.35vw;margin-left:3%;background-repeat:no-repeat;background-position:center;background-size:23px 13px;}.gg_images{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex}.gg_images_img{position:relative;-webkit-box-flex:1;-webkit-flex:1;-ms-flex:1;flex:1;background-repeat:no-repeat;background-position:center;background-size:cover;width:100%;padding-top:22%;}.gg_images_img:nth-child(2){margin: 0 3.3%;}.gg_images_img_after{position:absolute;left:0;top:0;width:100%;height:100%;border:solid 1px rgba(239,239,239,.5);box-sizing:border-box;}@media screen and (min-width: 640px){.gg_title_main{font-size:17px;height:20px;line-height: 20px}.gg_title_mark{height:13px}}";
			
					var borderStyle="#content #article_bottom a.gg_border{border:1px solid #dfdfdf;border-radius:6px;}";
					var borderRadiusStyle=".border_radius{border-radius:6px;}";
					var markStyle=".mark_active{display:block}";
			
					if ( _version >= 8.0) {
						baseStyle =baseStyle + borderRadiusStyle ;
					}
					if( ads_group == "wap_bottom_banner" ){
			
						baseStyle =baseStyle + borderStyle ;
					}
					if( _no_ad_tag != 1){
						baseStyle =baseStyle  + markStyle;
					}
					styleEle.innerHTML =baseStyle;
					
					document.head.appendChild(styleEle);
			
					pucl.innerHTML =
						\'<a class="gg_wrapper gg_border" onclick="ggs_click()" href="{{LINK_URL}}"><div class="ggs_div gg_content border_radius"><div class="gg_title"><div class="gg_title_main">{{AD_TITLE}}</div><div class="gg_title_mark mark_active" style="background-image:url(http://zkres3.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216);"></div></div><div class="gg_images"><div  class="gg_images_img border_radius" style="background-image:url({{AD_PIC_1}})"><div class="gg_images_img_after border_radius"></div></div><div class="gg_images_img border_radius" style="background-image:url({{AD_PIC_2}})"><div class="gg_images_img_after border_radius"></div></div><div class="gg_images_img border_radius" style="background-image:url({{AD_PIC_3}})"><div class="gg_images_img_after border_radius"></div></div ></div></div></a> <div id="ad_img_wrapper"></div> \';
				}
			}
			
			write_ad();
			
			// 将统计img插入进行广告统计
			var ad_count = function(cb) {
				var ad_img_wrapper = document.getElementById("ad_img_wrapper");
				var ad_html = \'{{SHOW_STAT_IMG}}\';
				ad_img_wrapper.innerHTML = ad_html;
				cb && cb();
			}
		';


		$js_temp = str_replace('{{VAR_NO_AD_TAG}}', $no_ad_tag, $js_temp);
		$js_temp = str_replace('{{VAR_VERSION}}', $oReq['_version']?$oReq['_version']:0, $js_temp);
		$js_temp = str_replace('{{VAR_ADS_GROUP}}', $oReq['ads_group'], $js_temp);

		$js_temp = str_replace('{{LINK_URL}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{SHOW_STAT_IMG}}', $show_stat_img, $js_temp);
		$js_temp = str_replace('{{CLICK_STAT_IMG}}', $click_stat_img, $js_temp);
		$js_temp = str_replace('{{AD_TITLE}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{AD_PIC_1}}', $ad_pic1, $js_temp);
		$js_temp = str_replace('{{AD_PIC_2}}', $ad_pic2, $js_temp);
		$js_temp = str_replace('{{AD_PIC_3}}', $ad_pic3, $js_temp);


		return $js_temp;
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_1')){
	/**
	 * wap版纯图片
	 */
	function zk_ads_wap_format_ads_type_1($ads, $oReq){

		return zk_ads_format_ads_type_1($ads, $oReq);
		
	}
}

/*
* 如果广告落地页是app内部地址，则需要按app内部格式拼装
*/
if(!function_exists('zk_ads_format_app_inner_content')){
	function zk_ads_format_app_inner_content(&$arrAds, $url, $oReq){
		$innerParams = zk_parse_param_from_inner_url($url, $oReq['_appid']);
		if(!$innerParams){
			return false;
		}
		unset($arrAds['special_info']['web_url']);
		unset($arrAds['type'], $arrAds['weburl']);

		if($innerParams["open_type"] == "a"){       //文章
			unset($arrAds['special_info']['open_type']);

			$articleInfo = $innerParams['article'];
			if($articleInfo['full_url']){
				$arrAds['full_url'] = $articleInfo['full_url'];
			}
			if($articleInfo["title"]){
				$arrAds['title'] = $articleInfo["title"];
			}
			if($articleInfo["auther_name"]){
				$arrAds['auther_name'] = $articleInfo["auther_name"];
			}
			if($articleInfo["date"]){
				$arrAds['date'] = $articleInfo["date"];
			}
		}elseif($innerParams["open_type"] == "topic"){   //专题
			$arrAds['special_type'] = "topic";
			$arrAds['special_info']["block_info"] = $innerParams['open_info'];
		
		}elseif($innerParams["open_type"] == "live"){   //直播
			$arrAds['type'] = "other";
			$arrAds['special_info']["open_type"] = "live";
			$arrAds['special_info']["live"] = $innerParams['live'];
		
		}elseif($innerParams["open_type"] == "post"){   //帖子
			$arrAds['type'] = "other";
			$arrAds['special_info']["open_type"] = "post";
			$arrAds['special_info']["post"] = $innerParams['post'];
		}
	}
}

/*
* 如果广告落地页是app内部地址，则需要按app内部格式拼装
*/
if(!function_exists('zk_ads_format_app_inner_content_for_jingcai')){
	function zk_ads_format_app_inner_content_for_jingcai(&$arrAds, $url, $oReq){
		$innerParams = zk_parse_param_from_inner_url($url, $oReq['_appid']);
		if(!$innerParams){
			return false;
		}
		if($innerParams["open_type"] == "a"){       //文章
			unset($arrAds['web'], $arrAds['type'], $innerParams['article']['auther_name']);
			$arrAds['article'] = $innerParams['article'];
		}
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_2')){
	/**
	 * wap版图+文
	 */
	function zk_ads_wap_format_ads_type_2($ads, $oReq){

		return zk_ads_format_ads_type_2($ads, $oReq);

	}
}

if(!function_exists('zk_ads_wap_format_ads_type_15')){
	/**
	 * wap版3图样式
	 */
	function zk_ads_wap_format_ads_type_15($ads, $oReq){
		return zk_ads_format_ads_type_15($ads, $oReq);	
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_3')){
	/**
	 * wap版纯文字
	 */
	function zk_ads_wap_format_ads_type_3($ads, $oReq){

		$rand = mt_rand(1, 3);
		if($rand == 1){
			return zk_ads_wap_format_ads_type_3a($ads, $oReq);
		}elseif($rand == 2){
			return zk_ads_wap_format_ads_type_3b($ads, $oReq);
		}else{
			return zk_ads_wap_format_ads_type_3c($ads, $oReq);
		}
		
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_3a')){
	/**
	 * wap版纯文字a
	 */
	function zk_ads_wap_format_ads_type_3a($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1b.png) repeat-x,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1a.png) no-repeat,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp1a.png) no-repeat right top;background-size:contain;background-clip:content-box,border-box,border-box;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						if(typeof ad_init == \'undefined\'){
							var ad_init = true;
							write_ad();
						}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
        //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	    //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_3b')){
	/**
	 * wap版纯文字b
	 */
	function zk_ads_wap_format_ads_type_3b($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2b.png) repeat-x,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2a.png) no-repeat,url(http://zkres3.myzaker.com/data/image/ad_pic/dsp2c.png) no-repeat right top;background-size:contain;background-clip:content-box,border-box,border-box;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						if(typeof ad_init == \'undefined\'){
							var ad_init = true;
							write_ad();
						}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
        //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	   //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_wap_format_ads_type_3c')){
	/**
	 * wap版纯文字c
	 */
	function zk_ads_wap_format_ads_type_3c($ads, $oReq){

		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq);
		}else{
			$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}";
		}

		$ad_content = $ads['ads_content'] ? $ads['ads_content'] : '';

		$js_temp = '
						var ggs_click = function(){
							var aImg = document.createElement("img");
							aImg.src="{{PHPCODE10}}";
						}

						var write_ad = function(){
							var pucl = document.getElementById("article_bottom");
							if(pucl != null){

								var ad_html = \'<a style="text-decoration: none;border:0;color:#000;padding:0px;display:block;" onclick="ggs_click();" href="{{PHPCODE1}}"><div style="margin:auto;margin-bottom:20px;padding:0px;width:100%;"><table style="text-align:center;margin:auto;padding:0px 12px;border:0px;width:100%;background:url(http://zkres3.myzaker.com/data/image/ad_pic/dsp3.png) repeat-x;background-size:contain;" cellpadding="0" cellspacing="0"><tr style="padding:0px;"><td style="height:50px;font-size:14px;line-height:135%;color:#00abff;overflow:hidden;padding:5px;">{{PHPCODE3}}</td></tr></table></div></a><div style="height:0px"></div>{{THIRD_VIEW_IMG}}\';

								pucl.innerHTML=ad_html;
							}
						}

						if(typeof ad_init == \'undefined\'){
							var ad_init = true;
							write_ad();
						}
					';

		// 	document.write(ad_html);
		$js_temp = str_replace('{{PHPCODE1}}', $ad_link_url, $js_temp);
		$js_temp = str_replace('{{PHPCODE3}}', $ad_content, $js_temp);
		$js_temp = str_replace('{{PHPCODE10}}', $ad_stat_url, $js_temp);
        //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
	    $oReq['ads_id']=(string)$ads['_id'];
	    $oReq['creative_id']=(string)$ads['creativeid'];
	    $show_stat_url='http://'.ZK_ADS_DOMAIN.'zk_ggs_show.php?'.http_build_query($oReq);
        $show_stat_url='<img style="display:none;border:0px;width:0px;height:0px" src="'.$show_stat_url.'" />';
		$js_temp = str_replace('{{THIRD_VIEW_IMG}}', $show_stat_url, $js_temp);

	    //----投放方式增加了CPM的,原来的曝光统计改为输出广告后再统计---Eddie-20150709------
		return $js_temp;
	}
}

if(!function_exists('zk_ads_format_ad_stat_url')){
	/**
	 * 构造广告统计url
	 */
	function zk_ads_format_ad_stat_url($ads, $oReq){

		$ad_stat_url = "http://".ZK_ADS_DOMAIN."zk_ggs_click.php?ads_id={$ads['_id']}&creative_id={$ads['creativeid']}&ads_group={$ads['ads_group']}&_udid={$oReq['_udid']}&_uid={$oReq['_uid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&new_app_id={$oReq['new_app_id']}&province_code={$oReq['_province']}&city_code={$oReq['_city']}&phone_brand={$oReq['phone_brand']}&f=".urlencode($oReq['f']);
		
		if(!empty($ads['tracker_url'])){
			$ad_stat_url .= "&ad_url=".urlencode($ads['tracker_url']);
		}
		if(!empty($ads['main_keyword'])){
			$ad_stat_url .= '&mkw='.base64_encode($ads['main_keyword']);
		}
		$ad_stat_url .= "&need_user_info=Y";

		if(!empty($oReq['f']) && $oReq['f'] != 'default'){
            $oReq['ads_id'] = $ads['_id'];
            $oReq['ads_group'] = $ads['ads_group'];
            $oReq['action'] = 'click';
            $ad_stat_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);
        }
        $ad_stat_url .= "&ucode=".zk_ads_get_url_code($ads['tracker_url']);

		return $ad_stat_url;
	}
}


if(!function_exists('zk_ads_format_ad_link_url')){
	/**
	 * 构造广告点击url
	 */
	function zk_ads_format_ad_link_url($ads, $oReq){

		//如果是zk内部软文地址，直接跳转
		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_link_url = $ads['ads_link_url'];
			return $ad_link_url;
		}

		//去掉链接里的target=_blank,target=_new
		$ads['ads_link_url'] = str_replace(array("?target=_blank","&target=_blank","?target=_new","&target=_new"), "", $ads['ads_link_url']);
		
		$ad_link_url = $ads['ads_link_url'];

		if($oReq['http_type'] == 1){  //不加http前缀
			$httpPrefix = '';
		}elseif($oReq['http_type'] == 2){  //https
			$httpPrefix = 'https:';
		}else{
			$httpPrefix = 'http:';		//http
		}

		$ad_url = urlencode($ads['ads_link_url']);
		
		if($oReq['ads_group'] == 'article_bottom_banner'){
			
			if(empty($ads['web_target'])){
				$ads['web_target'] = 'web';
			}
			
			$target_fix = $ads['web_target'] == 'safari' ? '&target=_blank':'&target=_new';
			
			if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
				$ad_link_url = $ads['ads_link_url'];
			}else{
// 				$ad_link_url = "http://".ZK_ADS_DOMAIN."zk_gg.php?t={$oReq['now']}{$target_fix}&a=".urlencode($ads['ads_link_url']);
				$ad_link_url = $httpPrefix. "//".ZK_ADS_DOMAIN."zk_ggs_click.php?ads_id={$ads['_id']}{$target_fix}&creative_id={$ads['creativeid']}&ads_group={$ads['ads_group']}&_udid={$oReq['_udid']}&_uid={$oReq['_uid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&new_app_id={$oReq['new_app_id']}&province_code={$oReq['_province']}&city_code={$oReq['_city']}&ad_udid={$oReq['ad_udid']}&phone_brand={$oReq['phone_brand']}&ad_url=".$ad_url;
				if (!empty($ads['loading_text'])){
					$ad_link_url .=  "&zk_loading_text=".rawurlencode($ads['loading_text']);
				}
			}
		}else{
            //如果没有ads_group,默认设置为other
            $ads['ads_group']= $ads['ads_group']? $ads['ads_group']: 'other';
            $ad_link_url = $httpPrefix. "//".ZK_ADS_DOMAIN."zk_ggs_click.php?ads_id={$ads['_id']}&creative_id={$ads['creativeid']}&ads_group={$ads['ads_group']}&_udid={$oReq['_udid']}&_uid={$oReq['_uid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&new_app_id={$oReq['new_app_id']}&province_code={$oReq['_province']}&city_code={$oReq['_city']}&phone_brand={$oReq['phone_brand']}&ad_url=".$ad_url;
        }

		//iphone有些版本对need_user_info=Y有bug（未登陆的情况会强制弹出登陆窗口）
		//而且发现用这个地址的地方基本都是一个web的结构，里面本身有need_user_info:"Y",所以针对有问题的版本不返回need_user_info=Y

		if($oReq['_appid']=='iphone' &&  in_array($oReq['_version'],array('6.42'))){

		}else{
			$ad_link_url .= "&need_user_info=Y";
		}
		//关键字cpm广告
		if(!empty($ads['keyword'])&&count($ads['keyword'])>0){
			$ad_link_url .= "&keyword=Y";
		}
		if(!empty($ads['main_keyword'])){
			$ad_link_url .= '&mkw='.base64_encode($ads['main_keyword']);
		}
		//作者和url
		if(!empty($oReq['author'])) {
			$ad_link_url .= "&author={$oReq['author']}";
		}
		if(!empty($oReq['url_host'])) {
			$ad_link_url .= "&url_host={$oReq['url_host']}";
		}

		if(!empty($ads['apk_name'])){
			$ad_link_url .= "&apk_name={$ads['apk_name']}";
		}
		//wap版渠道
		$ad_link_url .= "&f=".urlencode($oReq['f']);

		//加上媒体ID
	    if(!empty($oReq['cms_app_id'])){
			$ad_link_url .= "&cms_app_id=".$oReq['cms_app_id'];
		}
		
		//本地媒体推送到其他频道的原创文章标识
		if(!empty($oReq['rgcms_app_id']) && !empty($oReq['is_original'])){
			$ad_link_url .= "&rgcms_app_id=".$oReq['rgcms_app_id']."&is_original=".$oReq['is_original'];
		}

		if(!empty($oReq['f']) && $oReq['f'] != 'default'){
			$oReq['ads_id'] = $ads['_id'];
			$oReq['ads_group'] = $ads['ads_group'];
			$oReq['action'] = 'click';
			$ad_link_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);
		}
		$ad_link_url .= "&ucode=".zk_ads_get_url_code($ads['ads_link_url']);
		
		return $ad_link_url;
	}
}

if(!function_exists('zk_ads_format_ad_show_url')){
	/**
	 * 构造广告曝光url
	 */
	function zk_ads_format_ad_show_url($ads, $oReq){
		if($oReq['http_type'] == 1){  //不加http前缀
			$httpPrefix = '';
		}elseif($oReq['http_type'] == 2){  //https
			$httpPrefix = 'https:';
		}else{
			$httpPrefix = 'http:';		//http
		}

		$ads_group=$ads['ads_group']?$ads['ads_group']:$oReq['ads_group'];
		$show_url = $httpPrefix. "//".ZK_ADS_DOMAIN."zk_ggs_show.php?ads_id={$ads['_id']}&creative_id={$ads['creativeid']}&ads_group={$ads_group}&_udid={$oReq['_udid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&new_app_id={$oReq['new_app_id']}&need_user_info=Y&province_code={$oReq['_province']}&city_code={$oReq['_city']}&ad_udid={$oReq['ad_udid']}&phone_brand={$oReq['phone_brand']}";
		//wap版渠道
		$show_url .= "&f=".urlencode($oReq['f']);

		//加上媒体ID
		if(!empty($oReq['cms_app_id'])){
		    $show_url .= "&cms_app_id=".$oReq['cms_app_id'];
		}
		
		//本地媒体推送到其他频道的原创文章标识
		if(!empty($oReq['rgcms_app_id']) && !empty($oReq['is_original'])){
			$show_url .= "&rgcms_app_id=".$oReq['rgcms_app_id']."&is_original=".$oReq['is_original'];
		}
		//author和url_host
		if(!empty($oReq['author'])) {
			$show_url .= '&author='.$oReq['author'];
		}
		if(!empty($oReq['url_host'])) {
			$show_url .= '&url_host='.$oReq['url_host'];
		}
		if(!empty($ads['main_keyword'])){
			$show_url .= '&mkw='.base64_encode($ads['main_keyword']);
		}
		if(!empty($oReq['_idfa'])){
			$show_url .= '&_idfa='.$oReq['_idfa'];
		}
		if(!empty($oReq['_mac'])){
			$show_url .= '&_mac='.$oReq['_mac'];
		}
		if(!empty($oReq['_imei'])){
			$show_url .= '&_imei='.$oReq['_imei'];
		}

		$oReq['ads_id'] = $ads['_id'];
		$oReq['action'] = 'show';
		$oReq['ads_group'] = $ads['ads_group'];
		$show_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);
		
		return $show_url;
	}
}

if(!function_exists('zk_ads_make_page_order')){
	/**
	 * 频道内广告的排序
	 */
	function zk_ads_make_page_order($page_config,$arrAds,&$arrAdsArticles,$nt = 0){
		
		//构造出能嵌入的位置
		if($nt == 0){
			$ad_tpl_orders = array();
			$page5_tpl_group =  $page_config[5]['tpl_group'] ? intval($page_config[5]['tpl_group']) : 6;
			$page6_tpl_group =  $page_config[6]['tpl_group'] ? intval($page_config[6]['tpl_group']) : 6;
			for ($i=4;$i<$page5_tpl_group;$i++){
				$ad_tpl_orders[] = "5.$i";
			}
			// for ($i=4;$i<$page6_tpl_group;$i++){
			// 	$ad_tpl_orders[] = "6.$i";
			// }
		}else{
			$ad_tpl_orders[] = "2.".rand(4, 6);
			$ad_tpl_orders[] = "4.".rand(4, 6);
			$ad_tpl_orders[] = "6.".rand(4, 6);
		}
		
		foreach ($arrAds as $oAds){
			$order = "{$oAds['page']}.{$oAds['index']}";
			if(in_array($order, $ad_tpl_orders)){
				unset($ad_tpl_orders[array_search($order, $ad_tpl_orders)]);
			}
		}
		
		//随机设置位置
		if($nt == 0){
			foreach ($arrAdsArticles as &$oneAds){
				$akey = array_rand($ad_tpl_orders);
				$sOrder = $ad_tpl_orders[$akey];
				unset($ad_tpl_orders[$akey]);
				$arrOrder = explode(".", $sOrder);
				$oneAds['page'] = intval($arrOrder[0]);
				$oneAds['index'] = intval($arrOrder[1]);
			}
		}else{
			foreach ($arrAdsArticles as &$oneAds){
				$sOrder = array_shift($ad_tpl_orders);
				$arrOrder = explode(".", $sOrder);
				$oneAds['page'] = intval($arrOrder[0]);
				$oneAds['index'] = intval($arrOrder[1]);
			}
		}
// 		echo json_encode($ad_tpl_orders);exit;
			
	}
}

if(!function_exists('zk_ads_make_page_order_2')){
	/**
	 * 今日看点频道内广告的排序
	 */
	function zk_ads_make_page_order_2($page_config,$arrAds,&$arrAdsArticles,$nt = 0){
		$ad_tpl_orders = array();
		//构造出能嵌入的位置
		if($nt > 0){
			$ad_tpl_orders[] = "2.5";
			$ad_tpl_orders[] = "4.5";
		}
		$ad_tpl_orders[] = "6.".rand(4, 6);
		$ad_tpl_orders[] = "8.".rand(4, 6);
		$ad_tpl_orders[] = "10.".rand(4, 6);
		$ad_tpl_orders[] = "12.".rand(4, 6);
		$ad_tpl_orders[] = "14.".rand(4, 6);
		$ad_tpl_orders[] = "16.".rand(4, 6);
		$ad_tpl_orders[] = "18.".rand(4, 6);

		foreach ($arrAds as $oAds){
			$order = "{$oAds['page']}.{$oAds['index']}";
			if(in_array($order, $ad_tpl_orders)){
				unset($ad_tpl_orders[array_search($order, $ad_tpl_orders)]);
			}
		}

		//随机设置位置
// 		if($nt == 0){
// 			foreach ($arrAdsArticles as &$oneAds){
// 				$akey = array_rand($ad_tpl_orders);
// 				$sOrder = $ad_tpl_orders[$akey];
// 				unset($ad_tpl_orders[$akey]);
// 				$arrOrder = explode(".", $sOrder);
// 				$oneAds['page'] = intval($arrOrder[0]);
// 				$oneAds['index'] = intval($arrOrder[1]);
// 			}
// 		}else{
			foreach ($arrAdsArticles as &$oneAds){
				$sOrder = array_shift($ad_tpl_orders);
				$arrOrder = explode(".", $sOrder);
				$oneAds['page'] = intval($arrOrder[0]);
				$oneAds['index'] = intval($arrOrder[1]);
			}
// 		}
		// 		echo json_encode($ad_tpl_orders);exit;
			
	}
}

if(!function_exists('zk_ads_cache_get_key')){
	/**
	 * 获取加密后的Redis key
	 * @param string $key 原始key
	 * @param string $prefix key前缀
	 * @param int $length MD5之后取多少位
	 * 
	 * @return string $key
	 */
	function zk_ads_cache_get_key($key, $prefix='', $length=32){
		$key = md5($key);
		if(5< $length && $length < 32){
			$key = substr($key, 0, $length);
		}
		$key = $prefix.$key;
		return $key;
	}
}

if(!function_exists('zk_ads_cache_ads_show_count_incr')){
	/**
	 * 增加广告曝光数
	 * @param string $sAdsID
	 * @param int $nIcr
	 */
	function zk_ads_cache_ads_show_count_incr($sAdsID, $nIncr = 1, $expiredTime=0){
		if(!empty($sAdsID)){
			list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
			if(TRUE == $isRedisConnected){
				try {
					if(!is_numeric($expiredTime) || $expiredTime <= 0 ){
						$expiredTime = ZK_ADS_ADS_CACHE_EXPIRE;
					}
					$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$sAdsID, 'as_', 16);
					$re = $oRedis->incrBy($cacheKey, $nIncr);
					$re = $oRedis->setTimeout($cacheKey, $expiredTime);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_show_count_set')){
	/**
	 * 设置广告曝光数
	 * @param string $sAdsID
	 * @param int $nSet
	 */
	function zk_ads_cache_ads_show_count_set($sAdsID, $nSet){
		if(!empty($sAdsID) && $nSet > 0){
			list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
			if(TRUE == $isRedisConnected){
				try {
					$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$sAdsID, 'as_', 16);
					$re = $oRedis->set($cacheKey, $nSet);
					$re = $oRedis->setTimeout($cacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_show_count_get')){
	/**
	 * 读取广告曝光数
	 * @param string $sAdsID
	 * @return int
	 */
	function zk_ads_cache_ads_show_count_get($adsID){
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		
		if(FALSE == $isRedisConnected){
			return null;
		}
		
		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$sAdsID, 'as_', 16);
				array_push($keys, $cacheKey);
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			$sAdsID = $adsID;
			try {
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$sAdsID, 'as_', 16);
				$re = intval($oRedis->get($cacheKey));
			} catch (Exception $e) {
				$re = 0;
			}
		}
		
		return $re;
	}
}


if(!function_exists('zk_ads_cache_ads_click_count_incr')){
	/**
	 * 增加广告点击数
	 * @param string $sAdsID
	 * @param int $nIcr
	 */
	function zk_ads_cache_ads_click_count_incr($sAdsID, $nIncr = 1, $expiredTime=0){
		if(!empty($sAdsID)){
			list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
			if(TRUE == $isRedisConnected){
				try {
					if(!is_numeric($expiredTime) || $expiredTime <= 0 ){
						$expiredTime = ZK_ADS_ADS_CACHE_EXPIRE;
					}
					$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$sAdsID, 'ac_', 16);
					$re = $oRedis->incrBy($cacheKey, $nIncr);
					$re = $oRedis->setTimeout($cacheKey, $expiredTime);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_click_count_set')){
	/**
	 * 设置广告点击数
	 * @param string $sAdsID
	 * @param int $nSet
	 */
	function zk_ads_cache_ads_click_count_set($sAdsID, $nSet){
		if(!empty($sAdsID) && $nSet > 0){
			list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
			if(TRUE == $isRedisConnected){
				try {
					$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$sAdsID, 'ac_', 16);
					$re = $oRedis->set($cacheKey, $nSet);
					$re = $oRedis->setTimeout($cacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_click_count_get')){
	/**
	 * 读取广告点击数
	 * @param string $sAdsID
	 * @return int
	 */
	function zk_ads_cache_ads_click_count_get($adsID){
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		
		if(FALSE == $isRedisConnected){
			return null;
		}

		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$sAdsID, 'ac_', 16);
				array_push($keys, $cacheKey);
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			$sAdsID = $adsID;
			try {
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$sAdsID, 'ac_', 16);
				$re = intval($oRedis->get($cacheKey));
			} catch (Exception $e) {
				$re = 0;
			}
		}

		return $re;
	}
}

if(!function_exists('zk_ads_cache_ads_show_count_and_click_count_set')){
	/**
	 * 设置多个广告的曝光数和点击数
	 * @param <array> $arrAdsIds 广告或者广告计划id数组
	 * @param <string> $type  类型，ads:广告，campaign：广告计划
	 */
	function zk_ads_cache_ads_show_count_and_click_count_set($arrAdsIds, $type='ads'){
		if(!is_array($arrAdsIds) || empty($arrAdsIds)){
			return false;
		}
		$someAdsStat = zk_ads_db_ads_action_stat_get_batch($arrAdsIds, FALSE);
		if(empty($someAdsStat)){
			return false;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(!$isRedisConnected){
			return false;
		}
		try {
			//使用Redis pipeline，减少交互次数，提升效率
			$oRedis = $oRedis->multi(Redis::PIPELINE);
			foreach ($someAdsStat as $arrAdsStat){
				$ads_id = strval($arrAdsStat['ads_id']);
				if(empty($ads_id)){
					continue;
				}
				if(intval($arrAdsStat['showCount']) > 0){
					$showCacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$ads_id, 'as_', 16);
					$oRedis->set($showCacheKey, $arrAdsStat['showCount']);
					$oRedis->setTimeout($showCacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				}
				if(intval($arrAdsStat['clickCount']) > 0){
					$clickCacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$ads_id, 'ac_', 16);
					$oRedis->set($clickCacheKey, $arrAdsStat['clickCount']);
					$oRedis->setTimeout($clickCacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				}

				$tDate = date('Y-m-d');
				//如果是广告计划，需要更新当天的曝光量
				if($type=='campaign' && is_array($arrAdsStat['daily_shows']) && count($arrAdsStat['daily_shows']) > 0){
					$todayShows = intval($arrAdsStat['daily_shows'][$tDate]);
					$showCacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$ads_id."_".$tDate, 'as_', 16);
					if($todayShows > 0){
						$oRedis->set($showCacheKey, $todayShows);
						$oRedis->setTimeout($showCacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
					}
				}	
				//如果是广告计划，需要更新当天的点击量
				if($type=='campaign' && is_array($arrAdsStat['daily_clicks']) && count($arrAdsStat['daily_clicks']) > 0){
					$todayClicks = intval($arrAdsStat['daily_clicks'][$tDate]);
					$clickCacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$ads_id."_".$tDate, 'ac_', 16);
					if($todayClicks > 0){
						$oRedis->set($clickCacheKey, $todayClicks);
						$oRedis->setTimeout($clickCacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
					}
				}	
			}
			unset($someAdsStat);
			$oRedis->exec();

		} catch (Exception $e) {
			echo 'Caught exception: [show_count_and_click_count_set]: ',  $e->getMessage(), "\n";
		}
		return true;
	}
}

if(!function_exists('zk_ads_cache_delete_unnormal_ads')){
	/**
	 * 删除状态不正常的广告缓存
	 */
	function zk_ads_cache_delete_unnormal_ads(){
		$nNowTime = time();
		//获取状态不正常的广告
		$arrWheres = array(
				'start_time' => array('$lt' => $nNowTime),
				'end_time' => array('$gt' => $nNowTime),
				'stat' => array('$ne' => 1),
		);
		$arrAds = zk_ads_get_def_data($arrWheres, array(), array('_id'));
		if(empty($arrAds)){
			return false;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(!$isRedisConnected){
			return false;
		}
		try {
			//使用Redis pipeline，减少交互次数，提升效率
			$oRedis = $oRedis->multi(Redis::PIPELINE);
			foreach ($arrAds as $ads){
				$ads_id = strval($ads['_id']);
				if(empty($ads_id)){
					continue;
				}
				$oRedis->del(ZK_ADS_CACHE_SINGLE_ADS_DEF.$ads_id);
			}
			
			unset($arrAds);
			$oRedis->exec();	
			
		} catch (Exception $e) {
			echo 'Caught exception: [zk_ads_cache_delete_unnormal_ads]: ',  $e->getMessage(), "\n";
		}
		return true;
	}
}

if(!function_exists('zk_ads_db_ads_action_stat_get')){
	/**
	 * 从DB里读取广告动作统计数
	 * @param string $sAdsID
	 * @param bool $readOnly
	 * @return array
	 */
	function zk_ads_db_ads_action_stat_get($sAdsID, $readOnly = TRUE){
		
		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT,$readOnly);
		
		$db->where(array('ads_id' => strval($sAdsID)));
		$db->limit(1);
		
		$result = $db->get( ZK_MONGO_TB_ZK_ADS_ACTION_STAT );
		
		if(is_array($result) && count($result) == 1){
			return $result[0];
		}else{
			return $result;
		}
		
	}
}

if(!function_exists('zk_ads_db_ads_action_stat_get_batch')){
	/**
	 * 从DB里读取多个广告统计数
	 * @param <array> $adsIds 广告或者广告计划的id数组
	 * @param <bool> $readOnly
	 * @return array
	 */
	function zk_ads_db_ads_action_stat_get_batch($adsIds, $readOnly = TRUE){
		if(!is_array($adsIds) || empty($adsIds)){
			return array();
		}
		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT, $readOnly);
        $db->where_in('ads_id', $adsIds);

		return $db->get( ZK_MONGO_TB_ZK_ADS_ACTION_STAT );
	}
}

if(!function_exists('zk_ads_db_ads_action_stat_incr')){
	/**
	 * DB里广告动作统计数增加
	 * @param string $sAdsID
	 * @param int $showIncr
	 * @param int $arrDailyShowIncr
	 * @param int $clickIncr
	 * @param int $cost
	 * @return array
	 */
	function zk_ads_db_ads_action_stat_incr($sAdsID,$showIncr,$arrDailyShowIncr,$clickIncr,$arrDailyClickIncr = null,$cost=null, $arrDailyCostIncr=null){
		
		if(empty($sAdsID) || (empty($showIncr) && empty($clickIncr)) ){
			return false;
		}
		
		$arrAdsStat = zk_ads_db_ads_action_stat_get($sAdsID, FALSE);
		if(empty($arrAdsStat['ads_id'])){
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT);
				
			$insertArr = array(
					'ads_id' => strval($sAdsID),
					'showCount' => intval($showIncr),
					'clickCount' => intval($clickIncr),
					'cost' => (float)$cost,
					'add_time' => time()
			);
			
			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$insertArr['daily_clicks'][$date] += $incr;
				}
			}
			//---CPM每日曝光量---Eddie---20150701
			if(is_array($arrDailyShowIncr) && count($arrDailyShowIncr) > 0){
				foreach ($arrDailyShowIncr as $date => $incr){
					$insertArr['daily_shows'][$date] += $incr;
				}
			}
			//每天消耗统计
			if(is_array($arrDailyCostIncr) && count($arrDailyCostIncr) > 0){
				foreach ($arrDailyCostIncr as $date => $incr){
					$insertArr['daily_cost'][$date] += $incr;
				}
			}
			$result = $db->insert( ZK_MONGO_TB_ZK_ADS_ACTION_STAT, $insertArr );
		}else{
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT);
			
			$db->where(array('ads_id' => strval($sAdsID)));
			
			$updateArr = array();
			if(intval($showIncr) > 0){
				$updateArr['$inc']['showCount'] = intval($showIncr);
			}
			if(intval($clickIncr) > 0){
				$updateArr['$inc']['clickCount'] = intval($clickIncr);
			}
			if((float)$cost > 0){
				$updateArr['$inc']['cost'] =(float)$cost;
			}
			 
			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$updateArr['$inc']["daily_clicks.{$date}"] = intval($incr);
				}
			}
			//---CPM每日曝光量---Eddie---20150701
			if(is_array($arrDailyShowIncr) && count($arrDailyShowIncr) > 0){
				foreach ($arrDailyShowIncr as $date => $incr){
					$updateArr['$inc']["daily_shows.{$date}"] = intval($incr);
				}
			}
			//每日消耗统计
			if(is_array($arrDailyCostIncr) && count($arrDailyCostIncr) > 0){
				foreach ($arrDailyCostIncr as $date => $incr){
					$updateArr['$inc']["daily_cost.{$date}"] = floatval($incr);
				}
			}
			$result = $db->update( ZK_MONGO_TB_ZK_ADS_ACTION_STAT, $updateArr );
		}
		

		return $result;

	}
}


if(!function_exists('zk_ads_db_creative_stat_get')){
	/**
	 * 从DB里读取广告创意统计数
	 * @param string|array $creativeIds
	 * @param bool $readOnly
	 * @return array
	 */
	function zk_ads_db_creative_stat_get($creativeIds, $readOnly = TRUE){
		
		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_CREATIVE_STAT,$readOnly);
		if(is_array($creativeIds)){
          $db->where_in('creativeid',$creativeIds);
		  return $db->get( ZK_MONGO_TB_ZK_ADS_CREATIVE_STAT );

		}else{
		  $db->where(array('creativeid' => strval($creativeIds)));
		  return $db->getOne(ZK_MONGO_TB_ZK_ADS_CREATIVE_STAT);
		}
	}
}


if(!function_exists('zk_ads_db_creative_stat_incr')){
	/**
	 * DB里广告创意统计数增加
	 * @param string $sAdsID
	 * @param int $showIncr
	 * @param int $arrDailyShowIncr
	 * @param int $clickIncr
	 * @param int $cost
	 * @return array
	 */
	function zk_ads_db_creative_stat_incr($creativeId,$showIncr,$arrDailyShowIncr,$clickIncr,$arrDailyClickIncr = null,$cost=null, $arrDailyCostIncr=null){
		
		if(empty($creativeId) || (empty($showIncr) && empty($clickIncr)) ){
			return false;
		}
		$collectionName=ZK_MONGO_TB_ZK_ADS_CREATIVE_STAT;
		$arrAdsStat = zk_ads_db_creative_stat_get($creativeId, FALSE);
		if(empty($arrAdsStat['creativeid'])){
			$db = db_mongoDB_conn($collectionName);
				
			$insertArr = array(
					'creativeid' => strval($creativeId),
					'showCount' => intval($showIncr),
					'clickCount' => intval($clickIncr),
					'cost' => (float)$cost,
					'add_time' => time()
			);
			
			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$insertArr['daily_clicks'][$date] += $incr;
				}
			}
 			if(is_array($arrDailyShowIncr) && count($arrDailyShowIncr) > 0){
				foreach ($arrDailyShowIncr as $date => $incr){
					$insertArr['daily_shows'][$date] += $incr;
				}
			}
			//每天消耗统计
			if(is_array($arrDailyCostIncr) && count($arrDailyCostIncr) > 0){
				foreach ($arrDailyCostIncr as $date => $incr){
					$insertArr['daily_cost'][$date] += $incr;
				}
			}
			$result = $db->insert( $collectionName, $insertArr );
		}else{
			$db = db_mongoDB_conn($collectionName);
			
			$db->where(array('creativeid' => strval($creativeId)));
			
			$updateArr = array();
			if(intval($showIncr) > 0){
				$updateArr['$inc']['showCount'] = intval($showIncr);
			}
			if(intval($clickIncr) > 0){
				$updateArr['$inc']['clickCount'] = intval($clickIncr);
			}
			if((float)$cost > 0){
				$updateArr['$inc']['cost'] =(float)$cost;
			}
			 
			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$updateArr['$inc']["daily_clicks.{$date}"] = intval($incr);
				}
			}
 			if(is_array($arrDailyShowIncr) && count($arrDailyShowIncr) > 0){
				foreach ($arrDailyShowIncr as $date => $incr){
					$updateArr['$inc']["daily_shows.{$date}"] = intval($incr);
				}
			}
			//每日消耗统计
			if(is_array($arrDailyCostIncr) && count($arrDailyCostIncr) > 0){
				foreach ($arrDailyCostIncr as $date => $incr){
					$updateArr['$inc']["daily_cost.{$date}"] = floatval($incr);
				}
			}

			$result = $db->update( $collectionName, $updateArr );
		}
		

		return $result;

	}
}

if(!function_exists('zk_ads_db_ads_action_stat_save')){
	/**
	 * DB里广告动作统计数保存
	 * @param string $sAdsID
	 * @param int $showCount
	 * @param int $clickCount
	 * @return array
	 */
	function zk_ads_db_ads_action_stat_save($sAdsID,$showCount,$clickCount){

		if(empty($sAdsID) || (empty($showCount) && empty($clickIncr)) ){
			return false;
		}

		$arrAdsStat = zk_ads_db_ads_action_stat_get($sAdsID, FALSE);

		if(empty($arrAdsStat['ads_id'])){
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT);

			$insertArr = array(
					'ads_id' => strval($sAdsID),
					'showCount' => intval($showCount),
					'clickCount' => intval($clickCount),
					'add_time' => time()
			);

			$result = $db->insert( ZK_MONGO_TB_ZK_ADS_ACTION_STAT, $insertArr );
		}else{
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_ACTION_STAT);
			
			$db->where(array('ads_id' => strval($sAdsID)));
			
			$updateArr = array();
			
			if(intval($showCount) > 0){
				$updateArr['$set']['showCount'] = intval($showCount);
			}
			if(intval($clickCount) > 0){
				$updateArr['$set']['clickCount'] = intval($clickCount);
			}
				
			$result = $db->update( ZK_MONGO_TB_ZK_ADS_ACTION_STAT, $updateArr );
		}


		return $result;

	}
}

if(!function_exists('zk_ads_cache_user_ads_show_count_incr')){
	/**
	 * 增加用户看过广告的次数
	 * @param string $sUserID
	 * @param string $sAdsID
	 * @param int $nIncr
	 */
	function zk_ads_cache_user_ads_show_count_incr($sUserID, $sAdsID, $nIncr = 1, $expired = null){
		if(!empty($sUserID) && !empty($sAdsID)){
			
			if(!$expired){
				$expired = ZK_ADS_USER_ADS_SHOW_CACHE_EXPIRE;
			}
			
			list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache');
			if(TRUE == $isRedisConnected){
				try {
					$key = md5(ZK_ADS_CACHE_USER_ADS_SHOW_COUNT.$sUserID.$sAdsID);
					$re = $oRedis->incrBy($key, $nIncr);
					$re = $oRedis->setTimeout($key, $expired);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_third_party_click_count_normal')) {
    /**
     * 判断第三方渠道ip点击是否正常
     * @param $oReq
     * @param $ad_id
     * 正常 true 异常 false
     */
    function zk_ads_cache_third_party_click_count_normal($oReq, $ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {
            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(TRUE == $isRedisConnected) {
                try{
                    $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_CLICK_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tpc_', 16);
                    //key不存在代表第一次访问
                    if(!$oRedis->exists($key)) {
                        return true;
                    }
                    $countNum = intval($oRedis->get($key));
                    //超过最大值代表，返回false
                    if($countNum >= ZK_ADS_THIRD_ADS_IP_CLICK_LIMITATION) {
                        return false;
                    }
                }catch (Exception $e){
                    return true;
                }
            }
        }

        return true;
    }
}


if(!function_exists('zk_ads_cache_third_party_click_count_incr')) {
    /**
     * 增加第三方渠道ip点击次数
     * @param $oReq
     */
    function zk_ads_cache_third_party_click_count_incr($oReq,$ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {

            $expired = ZK_ADS_THIRD_ADS_IP_CACHE_EXPIRE; //过期时间

            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(TRUE == $isRedisConnected) {
                try{
                    $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_CLICK_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tpc_', 16);
                    if($oRedis->exists($key)) {
                        $oRedis->incr($key, 1);
                    }else{
                        $oRedis->incr($key, 1);
                        $oRedis->setTimeout($key, $expired);
                    }
                }catch (Exception $e){

                }
            }
        }
    }
}

if(!function_exists('zk_ads_cache_third_party_click_count_get')) {
    /**
     * 获取第三方渠道ip点击次数
     * @param $oReq
     */
    function zk_ads_cache_third_party_click_count_get($oReq, $ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {

            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(FALSE == $isRedisConnected){
                return null;
            }

            try{
                $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_CLICK_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tpc_', 16);
                $re = intval($oRedis->get($key));
            }catch (Exception $e){
                $re = 0;
            }

            return $re;
        }
    }
}



if(!function_exists('zk_ads_cache_third_party_show_count_normal')) {
    /**
     * 判断第三方渠道ip曝光是否正常
     * @param $oReq
     * @param $ad_id
     * 正常 true 异常 false
     */
    function zk_ads_cache_third_party_show_count_normal($oReq, $ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {
        	if($oReq['device_id'] == 'wifi8'){
        		return true;  //“花生地铁wifi”APP不限制IP曝光数，否则会过滤掉一大片。
        	}
            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(TRUE == $isRedisConnected) {
                try{
                    $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_SHOW_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tps_', 16);
                    //key不存在代表第一次访问
                    if(!$oRedis->exists($key)) {
                        return true;
                    }
                    $countNum = intval($oRedis->get($key));
                    //超过最大值代表，返回false
                    if($countNum >= ZK_ADS_THIRD_ADS_IP_SHOW_LIMITATION) {
                        return false;
                    }
                }catch (Exception $e){
                    return true;
                }
            }
        }

        return true;
    }
}


if(!function_exists('zk_ads_cache_third_party_show_count_incr')) {
    /**
     * 增加第三方渠道ip曝光次数
     * @param $oReq
     */
    function zk_ads_cache_third_party_show_count_incr($oReq,$ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {

            $expired = ZK_ADS_THIRD_ADS_IP_CACHE_EXPIRE; //过期时间

            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(TRUE == $isRedisConnected) {
                try{
                    $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_SHOW_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tps_', 16);
                    if($oRedis->exists($key)) {
                        $oRedis->incr($key, 1);
                    }else{
                        $oRedis->incr($key, 1);
                        $oRedis->setTimeout($key, $expired);
                    }
                }catch (Exception $e){

                }
            }
        }
    }
}

if(!function_exists('zk_ads_cache_third_party_show_count_get')) {
    /**
     * 获取第三方渠道ip曝光次数
     * @param $oReq
     */
    function zk_ads_cache_third_party_show_count_get($oReq, $ad_id) {
        if(!empty($oReq['ip']) && !empty($oReq['device_id']) ) {

            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            if(FALSE == $isRedisConnected){
                return null;
            }

            try{
                $key = zk_ads_cache_get_key(ZK_ADS_CACHE_USER_THIRD_PARTY_SHOW_COUNT.$oReq['device_id'].$oReq['ip'].$ad_id, 'tps_', 16);
                $re = intval($oRedis->get($key));
            }catch (Exception $e){
                $re = 0;
            }

            return $re;
        }
    }
}

if(!function_exists('zk_ads_cache_third_party_cost_day_incr')) {
	/**
	 * 第三方渠道今日曝光总金额累加
	 * @param $device_id 渠道id
	 * @param $cpm_price 单价
	 */
	function zk_ads_cache_third_party_cost_day_incr($device_id, $cpm_price) {
		
		if(empty($device_id) || empty($cpm_price)){
			return false;
		}

		//有设置日消耗上限的第三方渠道
		$arrThirdPartyCostDayID = zk_ads_config('third_party_cost_day');
		if(!array_key_exists($device_id, $arrThirdPartyCostDayID) || empty($arrThirdPartyCostDayID[$device_id]) ) {
			return false;
		}

		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(FALSE == $isRedisConnected){
			return null;
		}

		$expired = 3600*24; //过期时间

		try{
			$key = ZK_ADS_CACHE_THIRD_ADS_COST_DAY_LIMITATION.$device_id.date('Y-m-d');
			$priceOfCent = intval($cpm_price * 100); //按分记录

			if($oRedis->exists($key)) {
				$oRedis->incr($key, $priceOfCent);
			}else{
				$oRedis->incr($key, $priceOfCent);
				$oRedis->setTimeout($key, $expired);
			}
		}catch (Exception $e){

		}

	}
}

if(!function_exists('zk_ads_cache_third_party_cost_day_get')){
	/**
	 * 获取第三方渠道今日曝光总金额
	 * @param $device_id
	 */
	function zk_ads_cache_third_party_cost_day_get($device_id){

		if(empty($device_id)) {
			return false;
		}

		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(FALSE == $isRedisConnected){
			return null;
		}

		try{
			$key = ZK_ADS_CACHE_THIRD_ADS_COST_DAY_LIMITATION.$device_id.date('Y-m-d');
			$re = intval($oRedis->get($key));
		}catch (Exception $e){
			$re = 0;
		}

		return $re;

	}
}

if(!function_exists('zk_ads_cache_third_party_cost_day_not_exceeded')) {
	/**
	 * 第三方渠道是否未超出最大限制
	 * @param $device_id 第三方渠道id
	 * @return bool  true 未超出限制，请求rtb接口
	 * 				 false 超出限制，或者不需要限制
	 */
	function zk_ads_cache_third_party_cost_day_not_exceeded($device_id) {

		//有设置日消耗上限的第三方渠道
		$arrThirdPartyCostDayID = zk_ads_config('third_party_cost_day');
		if(!array_key_exists($device_id, $arrThirdPartyCostDayID) || empty($arrThirdPartyCostDayID[$device_id]) ) {
			return false;
		}

		//实时日消耗小于最大值
		$costDay = zk_ads_cache_third_party_cost_day_get($device_id);
		if($costDay < $arrThirdPartyCostDayID[$device_id]) {
			return true;
		}

		return false;
	}
}

if(!function_exists('zk_ads_cache_user_ads_show_count_get')){
	/**
	 * 获取用户看过广告的次数
	 * @param string $sUserID
	 * @return array
	 */
	function zk_ads_cache_user_ads_show_count_get($sUserID, $adsID){
		
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache');
		
		if(FALSE == $isRedisConnected){
			return null;
		}
		
		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				array_push($keys, md5(ZK_ADS_CACHE_USER_ADS_SHOW_COUNT.$sUserID.$sAdsID));
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			try {
				if(is_string($adsID)){
					$key = md5(ZK_ADS_CACHE_USER_ADS_SHOW_COUNT.$sUserID.$adsID);
					$re = intval($oRedis->get($key));
				}else{
					$re = 0;
				}
			} catch (Exception $e) {
				$re = 0;
			}
		}
		
		return $re;
	}
}

if(!function_exists('zk_ads_cache_user_ads_click_count_incr')){
	/**
	 * 增加用户点过广告的次数
	 * @param string $sUserID
	 * @param string $sAdsID
	 * @param int $nIncr
	 */
	function zk_ads_cache_user_ads_click_count_incr($sUserID, $sAdsID, $nIncr = 1, $expired = null){
		if(!empty($sUserID) && !empty($sAdsID)){
			
			if(!$expired){
				$expired = ZK_ADS_USER_ADS_CLICK_CACHE_EXPIRE;
			}
			list($newRedis, $isRedisConnected) = zk_ads_redis('user_cache');
			if(TRUE == $isRedisConnected){
				try {
					$key = md5(ZK_ADS_CACHE_USER_ADS_CLICK_COUNT.$sUserID.$sAdsID);
					$re = $newRedis->incrBy($key, $nIncr);
					$re = $newRedis->setTimeout($key, $expired);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_user_ads_click_count_get')){
	/**
	 * 获取用户点过的广告及次数
	 * @param string $nowDate
	 * @param string $sUserID
	 * @return array
	 */
	function zk_ads_cache_user_ads_click_count_get($sUserID, $adsID){

		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache');
		
		if(FALSE == $isRedisConnected){
			return null;
		}
		
		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				array_push($keys, md5(ZK_ADS_CACHE_USER_ADS_CLICK_COUNT.$sUserID.$sAdsID));
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			try {
				if(is_string($adsID)){
					$key = md5(ZK_ADS_CACHE_USER_ADS_CLICK_COUNT.$sUserID.$adsID);
					$re = intval($oRedis->get($key));
				}else{
					$re = 0;
				}
			} catch (Exception $e) {
				$re = 0;
			}
		}

		return $re;
	}
}

if(!function_exists('zk_ads_cache_user_ads_package_click_set')){
    /**
     * 用户点击过的广告组缓存
     * @param $sUserID 用户udid
     * @param $packageid 广告组packageid
     */
    function zk_ads_cache_user_ads_package_click_set($sUserID, $packageid)
    {
        if(!empty($sUserID) && !empty($packageid)){

            list($newRedis, $isRedisConnected) = zk_ads_redis('user_cache_set');
            if(TRUE == $isRedisConnected){
                try {
                	$key = md5(ZK_ADS_CACHE_USER_ADS_PACKAGE_CLICK_COUNT.$sUserID);
                	$expireTime = $newRedis->ttl($key);
					if($expireTime <= 0){
						$expireTime = ZK_ADS_USER_ADS_PACKAGE_CLICK_CACHE_EXPIRE; //7天有效期
					}
                    $re = $newRedis->sadd($key, $packageid);
                    $re = $newRedis->setTimeout($key, $expireTime);
                } catch (Exception $e) {

                }
            }
        }
    }
}

if(!function_exists('zk_ads_cache_user_ads_package_click_get')) {
    /**
     * 获取用户点击过的广告组
     * @param $sUserID 用户udid
     */
    function zk_ads_cache_user_ads_package_click_get($sUserID)
    {
        list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_set');

        if(FALSE == $isRedisConnected){
            return null;
        }

        try{
        	$key = md5(ZK_ADS_CACHE_USER_ADS_PACKAGE_CLICK_COUNT.$sUserID);
            $re = $oRedis->sMembers($key);
        }catch (Exception $e){
            $re = 0;
        }

        return $re;
    }
}

if(!function_exists('zk_ads_cache_set_user_latest_viewed_advertisers')){
	/**
	 * 记录用户看过的广告所属的广告主
	 * @param string $userid 用户id
	 * @param string $ads_group 广告位
	 * @param string $advId 广告主ID
	 */
	function zk_ads_cache_set_user_latest_viewed_advertisers($userid, $ads_group='', $advId){
		if(empty($userid) || empty($advId)){
			return false;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(TRUE == $isRedisConnected){
			try {
				$expiredTime = 3600*24; //一天缓存时间
				$key = md5(ZK_ADS_CACHE_USER_LATEST_VIEWED_ADVERTISERS.$userid.$ads_group);
				$re = $oRedis->get($key);
				$advIds = $re? json_decode($re, true): array();
				$advIds[] = $advId;
				$advIdsStr = json_encode(array_slice($advIds, -5)); //只保存最新的5个
				$re = $oRedis->set($key, $advIdsStr);
				$oRedis->setTimeout($key, $expiredTime);
				return $re;
			} catch (Exception $e) {
				return false;
			}
		}
		return false;
	}
}

if(!function_exists('zk_ads_cache_get_user_latest_viewed_advertisers')){
	/**
	 * 获取用户最近几次看过的广告所属的广告主
	 * @param string $userid 用户id
	 * @param string $ads_group 广告位
	 * @return array
	 */
	function zk_ads_cache_get_user_latest_viewed_advertisers($userid, $ads_group=''){
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(FALSE == $isRedisConnected){
			return array();
		}	
		try {
			$key = md5(ZK_ADS_CACHE_USER_LATEST_VIEWED_ADVERTISERS.$userid.$ads_group);
			$re = $oRedis->get($key);
			return $re? json_decode($re, true): array();
		} catch (Exception $e) {

		}
		return array();
	}
}

if(!function_exists('zk_ads_cache_delete_user_latest_viewed_advertisers')){
	/**
	 * 删除用户最近看过的广告主
	 * @param string $userid 用户id
	 * @param string $ads_group 广告位
	 * @return Boolean
	 */
	function zk_ads_cache_delete_user_latest_viewed_advertisers($userid, $ads_group=''){
		try {
			list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
			if($isRedisConnected){
				$key = md5(ZK_ADS_CACHE_USER_LATEST_VIEWED_ADVERTISERS.$userid.$ads_group);
				return $oRedis->delete($key);
			}
		} catch (Exception $e) {

		}
		return false;
	}
}

if(!function_exists('zk_ads_cache_multi_factor_show_count_incr')){
	/**
	 * 增加多因素综合的曝光数
	 * @param array $params 多因素数组
	 * @param int $nIcr 增加的次数
	 */
	function zk_ads_cache_multi_factor_show_count_incr($params, $nIncr = 1){
		if(!empty($params)){
			$key = md5(ZK_ADS_CACHE_MULTI_FACTOR_SHOW_COUNT. $params['_appid'].'-'.$params['ads_group'].'-'.$params['app_id'].'-'.$params['category_first'].'-'.$params['_province']);
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
			if(TRUE == $isRedisConnected){
				try {
					$oRedis->incrBy($key, $nIncr);
					$oRedis->setTimeout($key, 86400*30); //30天缓存
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_multi_factor_click_count_incr')){
	/**
	 * 增加多因素综合的点击数
	 * @param array $params 多因素数组
	 * @param int $nIcr 增加的次数
	 */
	function zk_ads_cache_multi_factor_click_count_incr($params, $nIncr = 1){
		if(!empty($params)){
			$key = md5(ZK_ADS_CACHE_MULTI_FACTOR_CLICK_COUNT. $params['_appid'].'-'.$params['ads_group'].'-'.$params['app_id'].'-'.$params['category_first'].'-'.$params['_province']);
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
			if(TRUE == $isRedisConnected){
				try {
					$oRedis->incrBy($key, $nIncr);
					$oRedis->setTimeout($key, 86400*30); //30天缓存
				} catch (Exception $e) {
				
				}
			}
		}
	}
}


if(!function_exists('zk_ads_cache_advertiser_show_count_incr')){
	/**
	 * 增加广告主的曝光数
	 * @param string $aid 广告主ID
	 * @param int $nIcr   增加的次数
	 */
	function zk_ads_cache_advertiser_show_count_incr($aid, $nIncr = 1){
		$key = md5(ZK_ADS_CACHE_ADVERTISER_ADS_SHOW_COUNT. $aid);
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(TRUE == $isRedisConnected){
			try {
				$oRedis->incrBy($key, $nIncr);
			} catch (Exception $e) {
				
			}
		}
	}
}

if(!function_exists('zk_ads_cache_advertiser_show_count_get')){
	/**
	 * 获取广告主的曝光数
	 * @param string/array $aids 广告主ID
	 */
	function zk_ads_cache_advertiser_show_count_get($aids){
		if(is_array($aids)){
			foreach ($aids as $key => $aid) {
				$keys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_SHOW_COUNT. $aid);
			}
		}else{
			$aid = strval($aids);
			$aids = array($aid);
			$keys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_SHOW_COUNT. $aid);
		}

		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		$result = array();
		if(TRUE == $isRedisConnected && $keys){
			$counts = $oRedis->mget($keys);
			$result = array_combine($aids, $counts);
		}
		return $result;
	}
}

if(!function_exists('zk_ads_cache_advertiser_click_count_incr')){
	/**
	 * 增加广告主的点击数
	 * @param string $aid 广告主ID
	 * @param int $nIcr   增加的次数
	 */
	function zk_ads_cache_advertiser_click_count_incr($aid, $nIncr = 1){
		$key = md5(ZK_ADS_CACHE_ADVERTISER_ADS_CLICK_COUNT. $aid);
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(TRUE == $isRedisConnected){
			try {
				$oRedis->incrBy($key, $nIncr);
			} catch (Exception $e) {
				
			}
		}
	}
}

if(!function_exists('zk_ads_cache_advertiser_click_count_get')){
	/**
	 * 获取广告主的点击数
	 * @param string/array $aids 广告主ID
	 */
	function zk_ads_cache_advertiser_click_count_get($aids){
		if(is_array($aids)){
			foreach ($aids as $key => $aid) {
				$keys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_CLICK_COUNT. $aid);
			}
		}else{
			$aid = strval($aids);
			$aids = array($aid);
			$keys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_CLICK_COUNT. $aid);
		}

		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		$result = array();
		if(TRUE == $isRedisConnected && $keys){
			$counts = $oRedis->mget($keys);
			$result = array_combine($aids, $counts);
		}
		return $result;
	}
}


if(!function_exists('zk_ads_cache_ads_partner_click_count_incr')){
	/**
	 * 增加广告在某合作方渠道的点击数
	 * @param string $partnerId  合作方ID
	 * @param string $adId       广告ID
	 * @param int $nIcr
	 */
	function zk_ads_cache_ads_partner_click_count_incr($partnerId, $adId, $nIncr = 1, $expiredTime=0){
		if(empty($partnerId) || empty($adId)){
			return false;
		}

		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(FALSE == $isRedisConnected){
			return false;
		}

		try {
			if(!is_numeric($expiredTime) || $expiredTime <= 0 ){
				$expiredTime = ZK_ADS_ADS_CACHE_EXPIRE;
			}
			$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_PARTNER_CLICK_COUNT.$adId.$partnerId, 'apc_', 16);
			$re = $oRedis->incrBy($cacheKey, $nIncr);
			$oRedis->setTimeout($cacheKey, $expiredTime);
			return $re;
		} catch (Exception $e) {
			return false;
		}

	}
}

if(!function_exists('zk_ads_cache_ads_partner_click_count_get')){
	/**
	 * 获取广告在某合作方渠道的点击数
	 * @param string $partnerId    合作方ID
	 * @param array/string $adsID  广告ID
	 * @return array/int
	 */
	function zk_ads_cache_ads_partner_click_count_get($partnerId, $adsID){
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		
		if(FALSE == $isRedisConnected || empty($adsID)){
			return null;
		}

		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $adId){
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_PARTNER_CLICK_COUNT.$adId.$partnerId, 'apc_', 16);
				array_push($keys, $cacheKey);
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			$adId = $adsID;
			try {
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_PARTNER_CLICK_COUNT.$adId.$partnerId, 'apc_', 16);
				$re = intval($oRedis->get($cacheKey));
			} catch (Exception $e) {
				$re = 0;
			}
		}

		return $re;
	}
}


if(!function_exists('zk_ads_predict_click_ratio')){
	/**
	 * 广告点击率预测
	 * 规则：如果是新广告，点击率为 “设备平台+广告位+频道+广告分类+地区”历史数据生成的点击率ctr1和广告主历史点击率crt2的加权平均值
	 *       如果不是新广告，点击率为 广告总的点击率ctr1和广告最近三天点击率crt2的加权平均值
	 * @param array $adsArr 广告信息
	 * @param array $params 请求参数
	 */
	function zk_ads_predict_click_ratio($adsArr,$params){
		if(empty($adsArr)){
			return array();
		}
		$adsIds = array();
		$adsShowKeys = $adsClickKeys = array();
		$today = date('Y-m-d');
		$yesterday = date('Y-m-d', strtotime('-1 day'));
		$daybofore = date('Y-m-d', strtotime('-2 day'));
		if(!empty($adsArr)){
			foreach ($adsArr as $key => $ads) {
				$adsid = strval($ads['_id']);
				$adsIds[] = $adsid;
				$params['category_first'] = $ads['category_first'];
				$adverShowKeys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_SHOW_COUNT. $ads['aid'] );
				$adverClickKeys[] = md5(ZK_ADS_CACHE_ADVERTISER_ADS_CLICK_COUNT. $ads['aid'] );
				$factorShowKeys[] = md5(ZK_ADS_CACHE_MULTI_FACTOR_SHOW_COUNT. $params['_appid'].'-'.$params['ads_group'].'-'.$params['app_id'].'-'.$params['category_first'].'-'.$params['_province']);
				$factorClickKeys[] = md5(ZK_ADS_CACHE_MULTI_FACTOR_CLICK_COUNT. $params['_appid'].'-'.$params['ads_group'].'-'.$params['app_id'].'-'.$params['category_first'].'-'.$params['_province']);
				
				$showCacheKey1 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$adsid."_".$today, 'as_', 16);
				$showCacheKey2 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$adsid."_".$yesterday, 'as_', 16);
				$showCacheKey3 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_SHOW_COUNT.$adsid."_".$daybofore, 'as_', 16);
				array_push($adsShowKeys, $showCacheKey1, $showCacheKey2, $showCacheKey3);

				$clickCacheKey1 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$adsid."_".$today, 'ac_', 16);
				$clickCacheKey2 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$adsid."_".$yesterday, 'ac_', 16);
				$clickCacheKey3 = zk_ads_cache_get_key(ZK_ADS_CACHE_ADS_CLICK_COUNT.$adsid."_".$daybofore, 'ac_', 16);
				array_push($adsClickKeys, $clickCacheKey1, $clickCacheKey2, $clickCacheKey3);
			}
		}

		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}

		list($adsRedis, $isAdsRedisConnected) = zk_ads_redis('ads_cache');

		if($isRedisConnected && $isAdsRedisConnected && !empty($adsIds)){
			//获取多因素组成的曝光数和点击数
			$factorsShowsTmp = $oRedis->mget($factorShowKeys);
			$factorsClicksTmp = $oRedis->mget($factorClickKeys);
			//获取广告主的曝光数和点击数
			$adverShowsTmp = $oRedis->mget($adverShowKeys);
			$adverClicksTmp = $oRedis->mget($adverClickKeys);
			//获取广告最近三天的曝光数和点击数
			$adsShowsTmp = $adsRedis->mget($adsShowKeys);
			$adsClicksTmp = $adsRedis->mget($adsClickKeys);
			foreach ($adsIds as $k => $id) {
				$factorsShows[$id] = $factorsShowsTmp[$k];
				$factorsClicks[$id] = $factorsClicksTmp[$k];
				$advertierShows[$id] = $adverShowsTmp[$k];
				$advertierClicks[$id] = $adverClicksTmp[$k];
				//最近三天的总曝光数
				$adsShows[$id] = $adsShowsTmp[$k*3] + $adsShowsTmp[$k*3+1] + $adsShowsTmp[$k*3+2];
				//最近三天的总点击数
				$adsClicks[$id] = $adsClicksTmp[$k*3] + $adsClicksTmp[$k*3+1] + $adsClicksTmp[$k*3+2];
			}
		}

		$defaultClickRatio = 0.003;
		$result = array();
		if(!empty($adsArr)){
			foreach ($adsArr as $key => $ads) {
				$adsid = strval($ads['_id']);
				//如果是新广告
				if($ads['click_count'] < 150){ 
					// “设备平台+广告位+频道+广告分类+地区” 对应的广告历史点击率
					$factorsClickRatio = ($factorsClicks[$adsid] && $factorsShows[$adsid]) ? round($factorsClicks[$adsid]/$factorsShows[$adsid], 5) : $defaultClickRatio;
					// 广告主的历史点击率
					$adverClickRatio = ($advertierClicks[$adsid] && $advertierShows[$adsid]) ? round($advertierClicks[$adsid]/$advertierShows[$adsid], 5) : $defaultClickRatio;
					if($advertierClicks[$adsid] < 10000){ //如果是新广告主
						$clickRatio = $factorsClickRatio;
					}else{
						$clickRatio = round($factorsClickRatio*0.4 + $adverClickRatio*0.6, 5);
					}
				}else{ //如果是投放了一段时间的广告
					//广告总点击率
					$adsClickRatio = ($ads['click_count'] && $ads['show_count']) ? round($ads['click_count']/$ads['show_count'], 5) : $defaultClickRatio;
					//最近三天的点击率
					$latestClickRatio = ($adsClicks[$adsid] && $adsShows[$adsid]) ? round($adsClicks[$adsid]/$adsShows[$adsid], 5) : $defaultClickRatio;
					$clickRatio = round($adsClickRatio*0.3 + $latestClickRatio*0.7, 5);
				}
				if($clickRatio < 0.001){
					$clickRatio = 0.001;
				}

				$result[$adsid] = $clickRatio;
			}
		}
		unset($adverShowKeys, $adverClickKeys, $factorShowKeys, $factorClickKeys, $adsShowKeys, $adsClickKeys);
		unset($adverShowsTmp, $adverClicksTmp, $factorsShowsTmp, $factorsClicksTmp, $adsShowsTmp, $adsClicksTmp);
		unset($advertierShows, $advertierClicks, $factorsShows, $factorsClicks, $adsShows, $adsClicks);
		
		return $result;
	}
}

if(!function_exists('zk_ads_queue_lpush')){
	/**
	 * 插入队列
	 * @param string $sQueue
	 * @param mixed $mData
	 */
	function zk_ads_queue_lpush($sQueue, $mData){
		
		if(!empty($sQueue) && !is_null($mData)){
			list($oRedis, $isRedisConnected) = zk_ads_redis('queue');
			if(TRUE == $isRedisConnected){
				try {
					$re = $oRedis->lPush($sQueue, json_encode($mData));
				} catch (Exception $e) {
				
				}
			}
		}
	}
	
}

if(!function_exists('zk_ads_queue_rpop')){
	/**
	 * 取出队列
	 * @param string $sQueue
	 * @return mixed
	 */
	function zk_ads_queue_rpop($sQueue){

		if(!empty($sQueue)){
			list($oRedis, $isRedisConnected) = zk_ads_redis('queue');
			
			if(TRUE == $isRedisConnected){
				try {
					$re = json_decode($oRedis->rPop($sQueue), true);
				} catch (Exception $e) {
					
				}
			}
			
			return $re;
		}
	}

}

if(!function_exists('zk_ads_get_article')){
	
	/**
	 * 获取文章信息
	 * @param unknown $pk
	 * @return array
	 */
	function zk_ads_get_article($pk){
		
		$keyRedis = ZK_ADS_CACHE_ARTICLE.strval($pk);
		
		$article = null;
		
		$article_cache = null;
		
		if(!empty($pk)){
		
			list($oRedis, $isRedisConnected) = zk_ads_redis('article_cache');
			
			if($isRedisConnected){
				try {
					$article_cache = unserialize($oRedis->get($keyRedis));
				} catch (Exception $e) {
					
				}
			}
			
			if(is_array($article_cache)){
				$article = $article_cache;
			}else{
				zk_ads_queue_lpush(ZK_ADS_QUEUE_ARTICLE,array('id'=>$pk));
				// $mongodb = db_mongoDB_conn ( ZK_MONGO_TB_ARTICLE, TRUE );
				// $article = $mongodb->select()->where ( array ( '_id' => new MongoId(strval($pk))))->getOne ( ZK_MONGO_TB_ARTICLE );
				// if($isRedisConnected == TRUE && $article['_id']){
				// 	try {
				// 		$re = $oRedis->set($keyRedis, serialize($article));
				// 		$re = $oRedis->setTimeout($keyRedis,3600*36);
						
				// 	} catch (Exception $e) {
					
				// 	}
				// }				

			}
		
		}
		
		return $article;
	}
	
}

if(!function_exists('zk_ads_get_appinfo')){
	
	/**
	 * 获取频道信息
	 * @param unknown $app_id
	 * @return array
	 */
	function zk_ads_get_appinfo($app_id){
		
		$appinfo = null;
		
		$appinfo_cache = null;
		
		if(!empty($app_id)){
			
			list($oRedis, $isRedisConnected) = zk_ads_redis('article_cache');
			try {
				if($isRedisConnected && $oRedis){
					$appinfo = unserialize($oRedis->get(ZK_ADS_CACHE_APPS.strval($app_id)));
				}else{
					$appinfo = array();
				}
			} catch (Exception $e) {
				$appinfo = array();
			}
			
		}
		
		return $appinfo;
		
	}
	
}

if(!function_exists('zk_ads_get_fav_ads_ids')){
	/**
	 * 获取喜爱分类对应的广告ID
	 * @param array $arrUserFavTypeIds
	 * @param array $oReq
	 * @return Ambigous <multitype:, mixed>
	 */
	function zk_ads_get_fav_ads_ids($arrUserFavTypeIds,$oReq){
		$arrUserFavAdsIds = array();
		if(count($arrUserFavTypeIds) > 0 && !empty($oReq['_appid'])){
			$arrAdsCacheKeys = array();

			//支持获取多个广告位
			if(is_array($oReq['multi_ads_group']) && !empty($oReq['multi_ads_group'])){
				$adsGroups = $oReq['multi_ads_group'];
			}else{
				$adsGroups = array($oReq['ads_group']);
			}
			foreach ($adsGroups as $key => $ads_group) {
				foreach ($arrUserFavTypeIds as $oneFavType){
					$cacheKey = ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$oReq['_appid'].$ads_group.$oneFavType;
					array_push($arrAdsCacheKeys, $cacheKey);
				}
			}
			zk_ads_add_log($arrAdsCacheKeys, 'user_fav_ad_keys');

			if(count($arrAdsCacheKeys) > 0){
				//cache_local是cache的从库，先连接从库
				list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
				zk_ads_add_log($isRedisConnected, 'is_cache_local_connected');
				if(!$isRedisConnected){
					list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
					zk_ads_add_log($isRedisConnected, 'is_cache_master_connected');
				}
				if(TRUE == $isRedisConnected){
					try {
						$arrUserFavAdsIds = call_user_func_array(array($oRedis, 'sUnion'), $arrAdsCacheKeys);
					} catch (Exception $e) {
					
					}
				}
			}
		}
		return $arrUserFavAdsIds;
	}
	
}


if(!function_exists('zk_ads_get_tag_ads_ids')){
	/**
	 * 获取标签对应的广告ID
	 * @param array $arrUserTags
	 * @param array $oReq
	 * @return Ambigous <multitype:, mixed>
	 */
	function zk_ads_get_tag_ads_ids($arrUserTags,$oReq){
		$arrUserTagAdsIds = array();
		if(count($arrUserTags) > 0 && !empty($oReq['_appid'])){
			$arrAdsCacheKeys = array();

			//支持获取多个广告位
			if(is_array($oReq['multi_ads_group']) && !empty($oReq['multi_ads_group'])){
				$adsGroups = $oReq['multi_ads_group'];
			}else{
				$adsGroups = array($oReq['ads_group']);
			}
			foreach ($adsGroups as $key => $ads_group) {
				foreach ($arrUserTags as $oneTag){
					$cacheKey = ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$oReq['_appid'].$ads_group.$oneTag;
					array_push($arrAdsCacheKeys, $cacheKey);
				}
			}

			if(count($arrAdsCacheKeys) > 0){
				//cache_local是cache的从库，先连接从库
				list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
				if(!$isRedisConnected){
					list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
				}
				if(TRUE == $isRedisConnected){
					try {
						$arrUserTagAdsIds = call_user_func_array(array($oRedis, 'sUnion'), $arrAdsCacheKeys);
					} catch (Exception $e) {
					
					}
				}
			}
		}
		return $arrUserTagAdsIds;
	}

}

if(!function_exists('zk_ads_get_channel_ads_ids')){
	/**
	 * 获取频道对应的广告ID
	 * @param array $arrUserTags
	 * @param array $oReq
	 * @return Ambigous <multitype:, mixed>
	 */
	function zk_ads_get_channel_ads_ids($appId,$oReq){
		
		if(!$appId){
			return array();
		}
		$arrUserChannelAdsIds = array();
		$arrAdsCacheKeys = array();

		//支持获取多个广告位
		if(is_array($oReq['multi_ads_group']) && !empty($oReq['multi_ads_group'])){
			$adsGroups = $oReq['multi_ads_group'];
		}else{
			$adsGroups = array($oReq['ads_group']);
		}
		foreach ($adsGroups as $key => $ads_group) {
			$cacheKey = ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$oReq['_appid'].$ads_group.$appId;
			array_push($arrAdsCacheKeys, $cacheKey);
		}

        //cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		if(TRUE == $isRedisConnected){
			try {
				$arrUserChannelAdsIds = call_user_func_array(array($oRedis, 'sUnion'), $arrAdsCacheKeys);
			} catch (Exception $e) {
			
			}
		}
		return $arrUserChannelAdsIds;
	}

}

if(!function_exists('zk_ads_get_location_ads_ids')){
	/**
	 * 获取地域对应的广告ID
	 * @param array $arrUserLocation
	 * @param array $oReq
	 * @return Ambigous <multitype:, mixed>
	 */
	function zk_ads_get_location_ads_ids($arrUserLocation,$oReq){
		$arrUserLocationAdsIds = array();
		if(count($arrUserLocation) > 0 && !empty($oReq['_appid'])){
			$arrAdsCacheKeys = array();

			//支持获取多个广告位
			if(is_array($oReq['multi_ads_group']) && !empty($oReq['multi_ads_group'])){
				$adsGroups = $oReq['multi_ads_group'];
			}else{
				$adsGroups = array($oReq['ads_group']);
			}
			foreach ($adsGroups as $key => $ads_group) {
				foreach ($arrUserLocation as $oneLocation){
					$cacheKey = ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$oReq['_appid'].$ads_group.$oneLocation;
					array_push($arrAdsCacheKeys, $cacheKey);
				}
			}
			zk_ads_add_log($arrAdsCacheKeys, 'user_location_ad_keys');

			if(count($arrAdsCacheKeys) > 0){
				//cache_local是cache的从库，先连接从库
				list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
				if(!$isRedisConnected){
					list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
				}
				if(TRUE == $isRedisConnected){
					try {
						$arrUserLocationAdsIds = call_user_func_array(array($oRedis, 'sUnion'), $arrAdsCacheKeys);
					} catch (Exception $e) {
					
					}
				}
			}
		}
		return $arrUserLocationAdsIds;
	}

}

/**
 * 关键字cpm广告逻辑
 */
if(!function_exists('zk_ads_check_keyword_cpm')) {
	function zk_ads_check_keyword_cpm($oReq)
	{
		//先不执行逻辑
		return false;

		/*
		//不出CPM广告的频道
		$disableCpmBlock = zk_ads_config('disable_cpm_ad_block');
		if(in_array($oReq['app_id'], $disableCpmBlock)) {
			return false;
		}

		//排除掉第三方广告输出
		if($oReq['mycheering_ad']){
			return false;
		}

		list($oRedis, $isRedisConnected) = zk_ads_redis('keyword');
		if (FALSE == $isRedisConnected) {
			return false;
		}


		try {
			//检查是否有在线的关键字cpm广告
			$onlineKeywords = $oRedis->sMembers(ZK_ADS_CACHE_ALL_KEYWORD_SET);
			if (!$onlineKeywords) {
				return false;
			}
			zk_ads_add_log($onlineKeywords, 'ADS_CACHE_ALL_KEYWORD_SET');

			//检查用户是否中了关键字
			$key = md5(ZK_ADS_CACHE_USER_VIEWED_KEYWORD_SET . $oReq['_udid']); //用户有关键字记录
			$userViewedKeywordSet = $oRedis->sMembers($key);
			if (!$userViewedKeywordSet) {
				return false;
			}

			//如果用户中的关键字和在线关键字不重合
			if (!array_intersect($userViewedKeywordSet, $onlineKeywords)) {
				return false;
			}

			//所有ads_group下的有关键字的广告id
			$arrAdsIds = $oRedis->sMembers(ZK_ADS_CACHE_ALL_KEYWORD_ADS_SET.$oReq['ads_group']);
			if (!$arrAdsIds) {
				return false;
			}

		} catch (Exception $e) {

		}

		zk_ads_add_log($userViewedKeywordSet, 'USER_VIEWED_KEYWORD_SET');
		zk_ads_add_log($arrAdsIds, 'ADS_CACHE_ALL_KEYWORD_ADS_SET');

		//暂时不执行下面逻辑，测试读取用户关键字缓存性能
		return false;

		//获取广告资源
		$adInfos = array();

		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		//获取广告数据
		if(class_exists('Yac')){
			//------------用yac减少读取流量 yexy---------------------
			$arrAdsIds=array_values(array_unique($arrAdsIds));
			$adKeys=array();
			$yac = new Yac();
			foreach ($arrAdsIds as $key => $value) {
				$oneAdsJson = $yac->get($value);

				if(!$oneAdsJson){
					$oneAdsJson=$oRedis->get(ZK_ADS_CACHE_SINGLE_ADS_DEF.$value);
					if($oneAdsJson){
						$yac->set($value, $oneAdsJson, 300);
					}
				}
				$adInfos[ZK_ADS_CACHE_SINGLE_ADS_DEF.$value] = $oneAdsJson;
			}
		}
		elseif($isRedisConnected){

			//------------用mget减少读取次数 Eddie---------------------
			$arrAdsIds=array_values(array_unique($arrAdsIds));
			$adKeys=array();
			foreach ($arrAdsIds as $key => $value) {
				$adKeys[]=ZK_ADS_CACHE_SINGLE_ADS_DEF.$value;
			}
			$adInfos=$oRedis->mget($adKeys);
		}


		$arrKeywordConfig = zk_ads_config('keyword_config');


		//广告主id
		$advertiserKeys = array();
		$advertiserCacheName = str_replace('{id}', '', ZK_DSP_CACHE_ADVERTISER_DATA);

		//获取广告详情
		$arrAdsDetails = array();
		$arrAdsDetailsTemp = array();

		//余额不足的广告ID
		$balanceInsufficient=array();
		//超过一周点击
		$arrTooManyGroupClicksWeek = array();
		//超过一天曝光
		$arrTooManyGroupShowsToday = array();
		//超过一周曝光
		$arrTooManyGroupShowsWeek = array();


		//广告组的id
		$arrAdGroupIds = array();
		$arrHitAdIds = array();
		$tDate = date("Y-m-d"); //哪一天


		//广告状态不是正常的广告
		$disableSpaces=array();
		//定向过滤掉的广告
		$redirectFilters=array();
		$redirectFilterIds=array();

		foreach ($adInfos as $key => $arrOneAdsDef) {
			if (!$arrOneAdsDef) {
				$disableSpaces[] = $arrAdsIds[$key];
				continue;
			}

			$arrOneAdsDef = json_decode($arrOneAdsDef, true);
			$adId = (string)$arrOneAdsDef['_id'];

			//广告状态不是正常的过滤掉
			if ($arrOneAdsDef['stat'] != 1) {
				$disableSpaces[] = $adId;
				continue;
			}

			//广告的关键字
			$hitKeywordArray = array_intersect($userViewedKeywordSet, $arrOneAdsDef['keyword']);
			if (count($hitKeywordArray) > 0) {
				//保存中了的关键字
				$arrHitAdIds[$adId] = $hitKeywordArray;
			} else {
				$redirectFilters['keyword_not_hit'][]=$adId;
				$redirectFilterIds[]=$adId;
				//如果没有中关键字，则过滤掉
				continue;
			}

			//只允许CPM
			if ($arrOneAdsDef['deliver_type'] != 2) {
				continue;
			}

			if (!empty($arrOneAdsDef['ad_group_id']) && !in_array($arrOneAdsDef['ad_group_id'], $arrAdGroupIds)) {
				$arrAdGroupIds[] = $arrOneAdsDef['ad_group_id'];
				$arrAdGroupIds[] = $arrOneAdsDef['ad_group_id'] . "_" . $tDate;
			}

			$arrAdsDetailsTemp[$adId] = $arrOneAdsDef;
			$advertiserKeys[] = $advertiserCacheName . $arrOneAdsDef['aid'];
		}

		zk_ads_add_log($redirectFilters, 'REDIRECT_FILTER_AD');
		//过滤掉状态不是正常的广告
		$arrAdsIds=array_diff($arrAdsIds,$disableSpaces,$redirectFilterIds);


		//用户总的广告点击次数
		$arrUserAdGroupClicks = zk_ads_cache_keyword_user_ads_click_count_get($oReq['_udid'], $arrAdGroupIds);
		zk_ads_add_log($arrUserAdGroupClicks, 'USER_KEYWORD_AD_GROUP_CLICK_COUNTS');

		//用户总的和今天看过广告的次数
		$arrUserAdGroupShows = zk_ads_cache_keyword_user_ads_show_count_get($oReq['_udid'], $arrAdGroupIds);
		zk_ads_add_log($arrUserAdGroupShows, 'USER_KEYWORD_AD_GROUP_SHOW_COUNTS');

		//获取广告主信息
		list($oRedis_ads, $isRedisConnected_ads) = zk_ads_redis('ads_cache');
		$advertiserInfosMap=array();
		if($isRedisConnected_ads&&$advertiserKeys){
			$advertiserKeys=array_values(array_unique($advertiserKeys));
			$advertiserInfos=$oRedis_ads->mget($advertiserKeys);
			if($advertiserInfos){
				foreach ($advertiserInfos as $key => $advertiser) {
					$advertiser=json_decode($advertiser,true);
					$advertiserInfosMap[(string)$advertiser['_id']]=$advertiser;
				}
			}
		}

		foreach ($arrAdsDetailsTemp as $oneAdsDef) {
			$oneAdsId = strval($oneAdsDef['_id']);

			//排除掉本周点击过的广告计划的广告
			$userWeekClicks = intval($arrUserAdGroupClicks[$oneAdsDef['ad_group_id']]);

			//排除掉当天曝光5次的、当周曝光30次的
			$userTodayShows = intval($arrUserAdGroupShows[$oneAdsDef['ad_group_id']."_".$tDate]); //用户当天曝光
			$userWeekShows = intval($arrUserAdGroupShows[$oneAdsDef['ad_group_id']]); //用户本周曝光


			$oneAdsDef['daily_target_views']=(int)$oneAdsDef['daily_target_views'];

			//广告主余额少于5元就不出广告
			$advertierInfo =$advertiserInfosMap[$oneAdsDef['aid']];
			if(!$advertierInfo ||
				($oneAdsDef['deliver_type']==2 && ($advertierInfo['balance'] -5*100) < intval($oneAdsDef['prize_weight']*100/1000))
				){
				$balanceInsufficient[]=$oneAdsId;
			}elseif($userWeekClicks >= 1){ //用户本周点击过
				array_push($arrTooManyGroupClicksWeek, $oneAdsId);
			}elseif($userTodayShows >= $arrKeywordConfig['maxShowDay']) { //用户当天曝光超出了当天限制
				array_push($arrTooManyGroupShowsToday, $oneAdsId);
			}elseif($userWeekShows >= $arrKeywordConfig['maxShowWeek']) { //用户本周曝光超出了当周限制
				array_push($arrTooManyGroupShowsWeek, $oneAdsId);
			}else{
				$arrAdsDetails[$oneAdsId] = $oneAdsDef;

				//-------------选择创意--------------
				// CPM 广告只有一个创意，直接用这个创意
					if($oneAdsDef['creatives']){
						$creativeCount=count($oneAdsDef['creatives']);
						$creatives=array_values($oneAdsDef['creatives']);
						$winId=rand(0,$creativeCount-1);
						$arrAdsDetails[$oneAdsId]['creativeid'] = $creatives[$winId]['_id'];
						if($creatives[$winId]['title']!=''){
							$arrAdsDetails[$oneAdsId]['ads_content']=$creatives[$winId]['title'];
						}
						if( !empty($creatives[$winId]['ads_pic']) ){
							$arrAdsDetails[$oneAdsId]['ads_pic']=$creatives[$winId]['ads_pic'];
						}
						if( !empty($creatives[$winId]['ads_short_pic']) ){
							$arrAdsDetails[$oneAdsId]['ads_short_pic']=$creatives[$winId]['ads_short_pic'];
						}
					}

			}


		}

		//没有匹配到任何广告
		if(empty($arrAdsDetails)){
			return false;
		}

		//改变广告图片的路径，防止被屏蔽掉
		if($arrAdsDetails){
			foreach ($arrAdsDetails as $key => $value) {
				if(!empty($value['ads_pic'])){
					$ads_pic = str_replace('adpic', 'pic1', $value['ads_pic']);
		    	    $arrAdsDetails[$key]['ads_pic'] = zk_ads_change_http_prefix($ads_pic, $oReq['http_type']);
				}
				if(!empty($value['ads_short_pic'])){
					$ads_short_pic = str_replace('adpic', 'pic1', $value['ads_short_pic']);
		    	    $arrAdsDetails[$key]['ads_short_pic'] = zk_ads_change_http_prefix($ads_short_pic, $oReq['http_type']);
				}
			}
		}

		//先注释掉余额不足的判断-----Eddie beta
		zk_ads_add_log($arrAdsIds, 'KEYWORD_ADS_IDS');
		zk_ads_add_log($balanceInsufficient, 'KEYWORD_ADS_BALANCE_INSUFFICIENT');
		zk_ads_add_log($arrTooManyGroupClicksWeek, 'USER_KEYWORD_TOO_MANY_GROUP_CLICK_WEEK');
		zk_ads_add_log($arrTooManyGroupShowsToday, 'USER_KEYWORD_TOO_MANY_GROUP_SHOW_TODAY');
		zk_ads_add_log($arrTooManyGroupShowsWeek, 'USER_KEYWORD_TOO_MANY_GROUP_SHOW_WEEK');
		$arrAdsIds = array_diff($arrAdsIds, $arrTooManyGroupClicksWeek,$arrTooManyGroupShowsToday,$arrTooManyGroupShowsWeek );
		zk_ads_add_log($arrAdsIds, 'KEYWORD_FINAL_ADS_IDS');



		//推荐广告 按照CPM单价排序广告，出最高的
		list($arrRecommendAdsID, $arrAdsScore) = zk_ads_keyword_cpm_recommend($oReq, $arrAdsDetails);

		zk_ads_add_log($arrAdsScore, 'KEYWORD_ADS_SCORE_SORT');
		zk_ads_add_log($arrRecommendAdsID, 'KEYWORD_RECOMMEND_ADS_ID');


		return array($arrRecommendAdsID, $arrAdsScore, $arrAdsDetails);
		*/
	}

}

if(!function_exists('zk_ads_keyword_cpm_recommend')) {
	/**
	 * Keyword CPM
	 * 关键字广告推荐
	 * @param $oReq
	 * @param $arrAdsDetails
	 * @return array
	 */
	function zk_ads_keyword_cpm_recommend($oReq, $arrAdsDetails)
	{
		$arrRecommendAdsID = null;
		$arrRecommendLog = array();
		$arrAdsScore = array();
		foreach ($arrAdsDetails as $sAdsID => $oneAdsDef){
			$baseScore = 100000;//因为要优先出关键字cpm广告，所以设置很高的分数确保排最前
			$adsScore = $baseScore * $oneAdsDef['prize_weight'];
			$arrRecommendLog[$sAdsID] = array(
				'score' => $adsScore,
				'ad_group_id'=>$oneAdsDef['ad_group_id']
			);

			$arrAdInfo=array('ads_id' => $sAdsID, 'score' => $adsScore);
			array_push($arrAdsScore, $arrAdInfo);
		}


		usort($arrAdsScore, "zk_ads_score_sort");

		if(count($arrAdsScore)>0){
			$arrRecommendAdsID = $arrAdsScore[0]['ads_id'];
		}

		return array($arrRecommendAdsID, $arrAdsScore);
	}
}

if(!function_exists('zk_ads_preload_keyword_ads_def')) {
	/**
	 * Keyword CPM
	 * 预加载关键字cpm广告计划
	 * @return bool
	 */
	function zk_ads_preload_keyword_ads_def()
	{
		$nNowTime = time();

		zk_ads_add_log("time: ".microtime(true), 'preload_begin');

		//redis
		list($oRedis, $isRedisConnected) = zk_ads_redis('keyword');
		if(false == $isRedisConnected) {
			zk_ads_add_log("connect redis error: ".microtime(true), 'ERROR');
			return false;
		}

		//获取所有的有关键字的广告资源
		$arrWheres = array(
			'start_time' => array('$lt' => $nNowTime),
			'end_time' => array('$gt' => $nNowTime),
			'stat' => 1,
			'keyword' => array('$gt' => array())
		);
		$arrAdsDef = zk_ads_get_def_data($arrWheres);

		if(empty($arrAdsDef)){
			zk_ads_add_log('Can not get ads info', 'ERROR2');
			return false;
		}
		zk_ads_add_log(count($arrAdsDef), 'ads_num');


		$allKeywords = array();
		$allKeywordAdsId=array();
		foreach($arrAdsDef as $value) {
			$allKeywordAdsId[$value['ads_group']][] = (string)$value['_id'];
			foreach($value['keyword'] as $keyword) {
				if(!in_array($keyword, $allKeywords)){
					$allKeywords[] = $keyword;
				}
			}
		}

		//广告资源十分钟缓存
		$cacheTime = 600;

		//所有关键字set
		$oRedis->delete(ZK_ADS_CACHE_ALL_KEYWORD_SET);
		foreach($allKeywords as $value) {
			$oRedis->sAdd(ZK_ADS_CACHE_ALL_KEYWORD_SET, $value);
		}
		$oRedis->setTimeout(ZK_ADS_CACHE_ALL_KEYWORD_SET, $cacheTime);

		//所有关键字广告的id
		foreach ($allKeywordAdsId as $adsGroup => $value) {
			$key = ZK_ADS_CACHE_ALL_KEYWORD_ADS_SET. $adsGroup;
			$oRedis->delete($key);
			foreach($value as $adsId) {
				$oRedis->sAdd($key, $adsId);
			}
			$oRedis->setTimeout($key, $cacheTime);
		}

		return true;

	}
}

if(!function_exists('zk_ads_cache_keyword_user_ads_click_count_get')) {
	/**
	 * Keyword CPM
	 * 获取用户点击过的关键字广告计划
	 * @param $sUserID
	 * @param $adsID
	 * @return array|int|null
	 */
	function zk_ads_cache_keyword_user_ads_click_count_get($sUserID, $adsID)
	{
		list($oRedis, $isRedisConnected) = zk_ads_redis('keyword_user');

		if(FALSE == $isRedisConnected){
			return null;
		}

		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				array_push($keys, md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_CLICK_COUNT.$sUserID.$sAdsID));
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			try {
				if(is_string($adsID)){
					$key = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_CLICK_COUNT.$sUserID.$adsID);
					$re = intval($oRedis->get($key));
				}else{
					$re = 0;
				}
			} catch (Exception $e) {
				$re = 0;
			}
		}

		return $re;
	}
}

if(!function_exists('zk_ads_cache_keyword_user_ads_click_count_incr')) {
	/**
	 * Keyword CPM
	 * 增加用户点击过的关键字广告计划
	 * @param $sUserID
	 * @param $sAdsID
	 * @param int $nIncr
	 */
	function zk_ads_cache_keyword_user_ads_click_count_incr($sUserID, $sAdsID, $nIncr = 1)
	{
		if(!empty($sUserID) && !empty($sAdsID)){

			$expireTimestamp = zk_ads_cache_ads_keyword_expire_timestamp();
			list($newRedis, $isRedisConnected) = zk_ads_redis('keyword_user');
			if(TRUE == $isRedisConnected){
				try {
					$key = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_CLICK_COUNT.$sUserID.$sAdsID);//本周
					$key2 = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_CLICK_COUNT.$sUserID.$sAdsID."_".date("Y-m-d"));//当天
					$re = $newRedis->incrBy($key, $nIncr);
					$re = $newRedis->expireAt($key, $expireTimestamp);
					$re = $newRedis->incrBy($key2, $nIncr);
					$re = $newRedis->expireAt($key2, $expireTimestamp);
				} catch (Exception $e) {

				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_keyword_user_ads_show_count_get')) {
	/**
	 * Keyword CPM
	 * 获取用户看过的关键字广告次数
	 * @param $sUserID
	 * @param $adsID
	 */
	function zk_ads_cache_keyword_user_ads_show_count_get($sUserID, $adsID)
	{
		list($oRedis, $isRedisConnected) = zk_ads_redis('keyword_user');

		if(FALSE == $isRedisConnected){
			return null;
		}

		if(is_array($adsID) && count($adsID) > 0){
			$keys = array();
			foreach ($adsID as $sAdsID){
				array_push($keys, md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_SHOW_COUNT.$sUserID.$sAdsID));
			}
			try {
				$values = $oRedis->mget($keys);
			} catch (Exception $e) {
				$values = array();
			}
			$re = array_combine($adsID, $values);
		}else{
			try {
				if(is_string($adsID)){
					$key = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_SHOW_COUNT.$sUserID.$adsID);
					$re = intval($oRedis->get($key));
				}else{
					$re = 0;
				}
			} catch (Exception $e) {
				$re = 0;
			}
		}

		return $re;
	}
}

if(!function_exists('zk_ads_cache_keyword_user_ads_show_count_incr')) {
	/**
	 * Keyword CPM
	 * 增加用户看过的关键字广告次数
	 * @param $sUserID
	 * @param $sAdsID
	 * @param int $nIncr
	 */
	function zk_ads_cache_keyword_user_ads_show_count_incr($sUserID, $sAdsID, $nIncr = 1)
	{
		if(!empty($sUserID) && !empty($sAdsID)){

			$expireTimestamp = zk_ads_cache_ads_keyword_expire_timestamp();
			list($newRedis, $isRedisConnected) = zk_ads_redis('keyword_user');
			if(TRUE == $isRedisConnected){
				try {
					$key = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_SHOW_COUNT.$sUserID.$sAdsID);//本周
					$key2 = md5(ZK_ADS_CACHE_KEYWORD_USER_ADS_SHOW_COUNT.$sUserID.$sAdsID."_". date('Y-m-d'));//当天
					$newRedis->incrBy($key, $nIncr);
					$newRedis->expireAt($key, $expireTimestamp);
					$newRedis->incrBy($key2, $nIncr);
					$newRedis->expireAt($key2, $expireTimestamp);
				} catch (Exception $e) {

				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_keyword_expire_timestamp')) {
	/**
	 * Keyword CPM
	 * 按照时间周期(如周日~周六)，获取本周的过期时间戳
	 * @return mixed
	 */
	function zk_ads_cache_ads_keyword_expire_timestamp()
	{
		$timestamp = strtotime("next sunday");
		return $timestamp;
	}
}

if(!function_exists('zk_ads_user_keyword_queue_import')) {
	/**
	 * Keyword CPM
	 * 处理同步队列的数据，写入用户关键字缓存
	 * @param $udid
	 * @param $keywords
	 * @param null $timestamp
	 */
	function zk_ads_user_keyword_queue_import($udid, $keywords,$timestamp=null)
	{
		list($oRedis, $isRedisConnected) = zk_ads_redis('keyword');
		if(FALSE == $isRedisConnected){
			return null;
		}

		try{
			$expireTimestamp = zk_ads_cache_ads_keyword_expire_timestamp();
			//判断时间戳是否过期，是上一个周期的数据
			if($timestamp){
				if((int)$timestamp < ($expireTimestamp - 7*24*3600) ){
					return null;
				}
			}
			$key = md5(ZK_ADS_CACHE_USER_VIEWED_KEYWORD_SET.$udid);
			foreach($keywords as $keyword) {
				$oRedis->sAdd($key, $keyword);
			}
			$oRedis->expireAt($key, $expireTimestamp);
		}catch (Exception $e) {

		}

	}
}

/**
* 广告筛选过滤
*/
if(!function_exists('zk_ads_common_filter')){
	function zk_ads_common_filter($adsArr, $oReq, $arrRecommendConfig){
		$adsInfoTmp = array();
		if(empty($adsArr)){
			return array();
		}

		$arrThirdPartyID = zk_ads_config('third_party_id');
		$disableCpmBlock = zk_ads_config('disable_cpm_ad_block');
		$superiorBlocks = zk_ads_config('superior_block'); //高级频道id
		$advertiserDisabledForPartner = zk_ads_config('advertiser_disabled_for_partner');
		$tongchengAdvs = zk_ads_config('58_tongcheng_advertisers');  //58同城相关广告主
		$outward_channels = zk_ads_config('outward_channels');  //外部导量渠道

		$audi_campaign_ids = zk_ads_config('audi_campaign_ids');  //奔驰特殊广告计划
		$audi_ad_white_list = zk_ads_config('audi_ad_white_list');  //奔驰特殊广告白名单用户

		$filter_white_list = zk_ads_config('filter_white_list_user');  //广告过滤逻辑白名单用户
		if(is_array($filter_white_list) && in_array($oReq['_udid'], $filter_white_list)){
			$is_white_list_user = true;
		}else{
			$is_white_list_user = false;
		}

		$advertiserKeys = array();
	    $advertiserCacheName= str_replace('{id}', '', ZK_DSP_CACHE_ADVERTISER_DATA);
	    $today = date("Y-m-d");
	    $genderMap=array(1=>'m',2=>'f');
		$carrierMap=array('中国移动'=>1,'中国联通'=>2,'中国电信'=>3);

		$gifAdsChannels = array(9,12,862);  //gif类型广告可以投放的频道：时尚，娱乐和测试频道

		$nowTime = time();
		$nowHour = intval(date('H'));
		$nowWeek = intval(date('N')); //星期一至星期日 1~7
		$nowWeekToken = $nowWeek.'-'.$nowHour;

		$currentHour = date("Y-m-d_H");
		$previousHour = date("Y-m-d_H", strtotime("-1 hour"));

		$campaignIds = $campaignIdsWithHour = $arrAdsIds = $creativeIds = array();

		$filter_function = "zk_ads_filter_ads_for_".$oReq['ads_group'];

		if($oReq['device_id'] == 'meizu_ssp'){
			$channel_id = 'meizu_flyme';
		}else{
			$channel_id = $oReq['device_id'];
		}
        $isLbsTypeTwo = false;

		foreach ($adsArr as $key => $arrOneAdsDef) {
			if(!$arrOneAdsDef){
			   	$unfitAds['not_exist'][]=$key;
			   	continue;
			}
	        if(!is_array($arrOneAdsDef)){
				$arrOneAdsDef = json_decode($arrOneAdsDef,true);
			}
	        $adId=(string)$arrOneAdsDef['_id'];
	        $campaignId = $arrOneAdsDef['ad_group_id'];
	        $adsGroup = $arrOneAdsDef['ads_group']; //广告位
	       
	        $adsSimpleInfo[$adId] = array(
	        	'name'=>$arrOneAdsDef['ads_name'], 
	        	'advertiser' => $arrOneAdsDef['sponsor'],
	        	'packageid' => $arrOneAdsDef['packageid'],
	        	'campaignid' => $arrOneAdsDef['ad_group_id'],
	        );

			//过滤掉状态不正常的广告
			if($arrOneAdsDef['stat']!=1){
	            $unfitAds['un_normal'][] = $adId;
	            continue;
			}
			//过滤没有正常创意的广告
			if(empty($arrOneAdsDef['creatives'])){
	            $unfitAds['no_creatives'][] = $adId;
	            continue;
			}

			/*
			//汽车频道文章底部暂时屏蔽DSP广告
			if($oReq['device_id'] == 'default' && $oReq['app_id'] == 7 && $oReq['ads_group'] == 'article_bottom_banner'){
				if(strtotime('2016-12-08 10:00:00') < $nowTime && $nowTime < strtotime('2016-12-15 10:00:00')){
					$unfitAds['disabled_for_channel_7'][] = $adId;
	            	continue;
				}
			}
			*/

			//过滤掉没有开放给第三方渠道的
			if(is_array($arrOneAdsDef['disabled_partners']) && in_array($oReq['device_id'], $arrOneAdsDef['disabled_partners'])){
				$unfitAds['disabled_for_partner'][] = $adId;
                continue;
			}
			//某些广告主的广告不投放到第三方渠道
			if($oReq['device_id'] != 'default' && in_array($arrOneAdsDef['aid'], $advertiserDisabledForPartner) ){
				$unfitAds['advertiser_disabled_for_partner'][] = $adId;
				continue;
			}
			//58同城的广告只投放到特定广告位，其他位置不投放
			if(in_array($arrOneAdsDef['aid'], $tongchengAdvs)) {
				$unfitAds['disabled_58tongcheng_ads'][] = $adId;
				continue;
			}

			//过滤掉没有被第三方渠道采用的
			if(in_array($channel_id, $arrThirdPartyID)){
				if(empty($arrOneAdsDef['for_third_party']) || !is_array($arrOneAdsDef['for_third_party']) || !in_array($channel_id, $arrOneAdsDef['for_third_party'])){
					$unfitAds['rejected_by_partner'][] = $adId;
                	continue;
				}
			}
			//过滤掉没有开放给wap内容渠道的
			if($oReq['ads_group'] == 'wap_bottom_banner' || $oReq['ads_group'] == 'wap_jingcai'){
				if(is_array($arrOneAdsDef['disabled_wap_partners']) && in_array($oReq['device_id'], $arrOneAdsDef['disabled_wap_partners'])){
					$unfitAds['disabled_for_wap_partner'][] = $adId;
					continue;
				}
			}
			//过滤掉微信里面的App下载类型
            if($arrOneAdsDef['ad_type']==2 && $oReq['device_id']=='weixin') {
                $unfitAds['disabled_for_weixin'][] = $adId;
                continue;
            }

			//热点推荐和频道列表的CPM广告出价要大于等于24元
			if( in_array($oReq['ads_group'], array('article_recommend', 'block_page')) && $arrOneAdsDef['deliver_type'] == 2){
				if($arrOneAdsDef['prize_weight'] < 24){
					$unfitAds['unmatch_lowest_price'][] = $adId;
					continue;
				}
			}
            //开启强制匹配模式时, 没有命中喜好分类的广告就过滤掉
			if($oReq['device_id'] == 'default' && $arrOneAdsDef['strict_match'] == 1 && !in_array($oReq['strict_category_id'], $arrOneAdsDef['favour_category']) ){
				$unfitAds['unmatch_strict_category'][] = $adId;
				continue;
			}
			//如果广告设置了关键词，就必须和文章关键词匹配
			if(is_array($arrOneAdsDef['keywords']) && !empty($arrOneAdsDef['keywords'])){
				if(is_array($oReq['article_tags'])){
					$matchedKeywords = array_intersect($arrOneAdsDef['keywords'], $oReq['article_tags']);
					$matchedKeywords = array_values($matchedKeywords);
				}
				if(empty($matchedKeywords)){
					$unfitAds['unmatch_keywords'][] = $adId;
					continue;
				}else{
					$arrOneAdsDef['main_keyword'] = $matchedKeywords[0];
				}
			}
			//过滤掉没有命中频道的
			if($oReq['device_id'] == 'default' && !empty($arrOneAdsDef['channel']) && !in_array($oReq['app_id'], $arrOneAdsDef['channel']) ){
				$unfitAds['unmatch_channel'][] = $adId;
				continue;
			}
			/*
			//Gif类型广告只投放到娱乐和时尚频道
			if($oReq['device_id'] == 'default' && $arrOneAdsDef['ads_type'] == 17 && !in_array($oReq['app_id'], $gifAdsChannels) ){
				$unfitAds['unmatch_channel'][] = $adId;
				continue;
			}
			*/

			if(in_array($campaignId, $audi_campaign_ids)){           //奥迪特殊广告启用白名单
				if(!in_array($oReq['_udid'], $audi_ad_white_list)){
					//过滤掉没有命中地域的
					if($arrOneAdsDef['location'] && !in_array($oReq['_city'],$arrOneAdsDef['location']) && !in_array($oReq['_province'], $arrOneAdsDef['location'])){
						$unfitAds['unmatch_location'][] = $adId;
		            	continue;
					}
				}
			}else{
		        //过滤掉没有命中地域的
				if($arrOneAdsDef['location'] && !in_array($oReq['_city'],$arrOneAdsDef['location']) && !in_array($oReq['_province'], $arrOneAdsDef['location'])){
					$unfitAds['unmatch_location'][] = $adId;
		            continue;
				}
			}

            //实时定向lbs过滤
            if (!empty($arrOneAdsDef['lbs_type'])) {

                if (empty($arrOneAdsDef['lbs_location'])) {
                    $unfitAds['no_lbs_location'][] = $adId;
                    continue;
                }

                if ($arrOneAdsDef['lbs_type'] == 1) {
                    if (empty($oReq['_lat']) || empty($oReq['_lon'])) {
                        $unfitAds['no_lbs_lal'][] = $adId;
                        continue;
                    }

                    load_class('zk_lbs_location');
                    $count = 0;
                    foreach ($arrOneAdsDef['lbs_location'] as $item) {
                        if ($item['city'] != $oReq['_city']) {
                            continue;
                        }

                        $distance = Zk_Lbs_Location::getInstance()->getDistance($oReq['_lat'], $oReq['_lon'], $item['lat'], $item['lon']);
                        if ($distance < $item['radius']) {
                            $count++;
                        }
                    }

                    if ($count == 0) {
                        $unfitAds['unmatch_lbs_radius'][] = $adId;
                        continue;
                    }
                } else if ($arrOneAdsDef['lbs_type'] == 2){
                    $isLbsTypeTwo = true;
                }
            }

	        //过滤掉没有命中手机品牌的
			if(!empty($arrOneAdsDef['phone_brand']) && $oReq['phone_brand'] ){
				foreach ($arrOneAdsDef['phone_brand'] as $value) {
					$brands[] = strtolower($value);
				}
				$oReq['phone_brand'] = strtolower($oReq['phone_brand']);
				if(!in_array($oReq['phone_brand'], $brands)){
					$unfitAds['unmatch_phone_brand'][] = $adId;
	            	continue;
				}
			}
			//过滤掉没有命中运营商的
			if($arrOneAdsDef['carrier'] && $oReq['carrier'] && !in_array($carrierMap[$oReq['carrier']],$arrOneAdsDef['carrier'])){
				$unfitAds['unmatch_carrier'][] = $adId;
                continue;
			}
			//过滤掉没有命中网络类型的
			if($arrOneAdsDef['network_type'] && $oReq['_net'] && !in_array($oReq['_net'], $arrOneAdsDef['network_type'])){
				$unfitAds['unmatch_network'][] = $adId;
                continue;
			}
			//过滤掉没有命中性别的
			if($arrOneAdsDef['sex'] && $oReq['sex'] && $oReq['sex']!=$genderMap[$arrOneAdsDef['sex']] ){
				$unfitAds['unmatch_gender'][]=$adId;
                continue;
			}  
			//过滤掉没有命中投放时间段的
			//星期一到星期天的24小时选择
			if( is_array($arrOneAdsDef['deliver_time_week']) && !empty($arrOneAdsDef['deliver_time_week']) && !in_array($nowWeekToken, $arrOneAdsDef['deliver_time_week']) ) {
				$unfitAds['unmatch_deliver_time'][] = $adId;
				continue;
			}
			elseif(is_array($arrOneAdsDef['deliver_time']) && !empty($arrOneAdsDef['deliver_time']) && !in_array($nowHour, $arrOneAdsDef['deliver_time'])){
				$unfitAds['unmatch_deliver_time'][] = $adId;
                continue;
			}
			//如果是视频CPM广告，过滤掉android版本过低的，client_sdk_version>16才出
            if(!empty($arrOneAdsDef['video_url']) && !empty($arrOneAdsDef['video_cover']) && $oReq['_appid'] == 'androidphone'){
                if(!empty($oReq['client_sdk_version']) && (int)$oReq['client_sdk_version'] <= 16) {
                	$unfitAds['unmatch_video_ad_demand'][] = $adId;
                    continue;
                }
            }
            //过滤掉本地频道的cpm广告
			if($arrOneAdsDef['deliver_type'] == 2 && !empty($oReq['app_id']) && in_array($oReq['app_id'], $disableCpmBlock)) {
				$unfitAds['disabled_cpm_for_local_media'][] = $adId;
				continue;
			}

			//如果是安卓的apk类下载广告，屏蔽掉已经下载过的用户
			if($oReq['_appid'] == 'androidphone' && !empty($arrOneAdsDef['apk_name']) && !empty($oReq['udid'])){
				$isApkDownloaded = zk_ads_check_apk_user_used($arrOneAdsDef['apk_name'], $oReq['udid']);
				if($isApkDownloaded) {
					$unfitAds['apk_is_downloaded'][] = $adId;
					continue;
				}
			}

		
			//当前广告位过滤不合适的广告
			if(function_exists($filter_function)){
				list($filterOut, $filterReason) = $filter_function($arrOneAdsDef, $oReq);
				if($filterOut){
					$unfitAds[$filterReason][] = $adId;
					continue;
				}
			}

			if(!empty($arrOneAdsDef['ad_group_id']) && !in_array($arrOneAdsDef['ad_group_id'], $campaignIds)){
				$campaignIds[] = $arrOneAdsDef['ad_group_id'];
				$campaignIds[] = $arrOneAdsDef['ad_group_id']."_".$today;
				$campaignIdsWithHour[] = $arrOneAdsDef['ad_group_id']."_".$currentHour;
				$campaignIdsWithHour[] = $arrOneAdsDef['ad_group_id']."_".$previousHour;
			}
			foreach ($arrOneAdsDef['creatives'] as $creative) {
				$creativeIds[] = $creative['_id'];
			}

			$adsInfoTmp[$adId] = $arrOneAdsDef;
			$arrAdsIds[] = $adId;
			$advertiserKeys[] = $advertiserCacheName.$arrOneAdsDef['aid'];
			if($arrOneAdsDef['aid'] && $arrOneAdsDef['product']){
				$aid = $arrOneAdsDef['aid'];
				$product = $arrOneAdsDef['product'];
				if(!in_array($product, $advertiserProducts[$aid])){
					$advertiserProducts[$aid][] = $product;
				}
			}
		}

		zk_ads_add_log($adsSimpleInfo, 'ads_simple_info');

		if(empty($adsInfoTmp)){
			zk_ads_add_log($unfitAds, 'unfit_ads');
			zk_ads_add_log(array(), 'matched_ads');
			zk_ads_record_no_ads_reason($currentHour, $oReq, 'ads_setting');
			return array();
		}

		//获取用户常去的地点
        if (!empty($oReq['_udid']) && $isLbsTypeTwo && $oReq['device_id'] == 'default') {
            load_helper('zk_lbs_ads');
            $udidLocations = zk_get_user_often_appear_location($oReq['_udid']);
            zk_ads_add_log($udidLocations, 'LBS_USER_LOCATION_ID');
        }


		//广告的展示次数
		$arrAdsShows = zk_ads_cache_ads_show_count_get($arrAdsIds);
		zk_ads_add_log($arrAdsShows, 'ADS_SHOW_COUNTS');
		
		//广告的点击次数
		$arrAdsClicks = zk_ads_cache_ads_click_count_get($arrAdsIds);
		zk_ads_add_log($arrAdsClicks, 'ADS_CLICK_COUNTS');

		$campaignIdsWithTime = array_merge($campaignIds, $campaignIdsWithHour);
		//广告计划总的曝光次数 和今天的曝光次数
		$arrCampaignShows = zk_ads_cache_ads_show_count_get($campaignIdsWithTime);
		zk_ads_add_log($arrCampaignShows, 'CAMPAIGNS_SHOW_COUNTS');

		//广告计划总的点击次数 和今天的点击次数
		$arrCampaignClicks = zk_ads_cache_ads_click_count_get($campaignIdsWithTime);
		zk_ads_add_log($arrCampaignClicks, 'CAMPAIGNS_CLICK_COUNTS');

		//用户看过这些广告计划总的次数和今天的次数
		$arrUserCampaignShows = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $campaignIds);
		zk_ads_add_log($arrUserCampaignShows, 'USER_CAMPAIGN_SHOW_COUNTS');
	    
		//用户点击过这些广告计划总的次数和今天的次数
		if($oReq['device_id'] == 'default'){
			$arrUserCampaignClicks = zk_ads_cache_user_ads_click_count_get($oReq['_udid'], $campaignIds);
		}else{
			$arrUserCampaignClicks = array();  //外部渠道不需要获取
		}
		zk_ads_add_log($arrUserCampaignClicks, 'USER_CAMPAIGN_CLICK_COUNTS');

		//用户点击过的广告组
        $arrUserAdPackageClicks = zk_ads_cache_user_ads_package_click_get($oReq['_udid']);
        zk_ads_add_log($arrUserAdPackageClicks, 'USER_AD_PACKAGE_CLICK');

        //广告计划在该渠道的点击数
        $adsPartnerClicks = zk_ads_cache_ads_partner_click_count_get($oReq['device_id'], $campaignIds);
        zk_ads_add_log($adsPartnerClicks, 'CAMPAIGNS_PARTNER_CLICK_COUNT');

        if($oReq['device_id'] == 'default'){
			//用户最近看过某些产品的次数
			$productShows = zk_ads_cache_user_product_show_count_get($oReq['_udid'], $advertiserProducts);
		}else{   //外部渠道不需要获取
			$productShows = array();	  
		}

		zk_ads_add_log($productShows, 'USER_PRODUCT_SHOW_COUNT');

		$isActiveUser = zk_ads_is_active_user($oReq['_udid']); //是否是活跃用户
		zk_ads_add_log($isActiveUser, 'IS_ACTIVE_USER');

		$productMaxShowCount = $isActiveUser ? 15 : $arrRecommendConfig['dailyProductMaxShow'];

		//广告位点击率
		$adsGroupCtr = zk_ads_config('ads_group_base_ctr');
		zk_ads_add_log($adsGroupCtr, 'ADS_GROUP_CTR');
		
		/*
		//广告创意的曝光数
		$creativeShows = zk_ads_cache_creative_ads_group_show_count_get($creativeIds, $oReq['ads_group']);
		zk_ads_add_log($creativeShows, 'CREATIVE_SHOW_COUNT');
		//广告创意的点击数
		$creativeClicks = zk_ads_cache_creative_ads_group_click_count_get($creativeIds, $oReq['ads_group']);
		zk_ads_add_log($creativeClicks, 'CREATIVE_CLICK_COUNT');
		*/

		//获取广告主信息
		list($oRedis_ads, $isRedisConnected_ads) = zk_ads_redis('ads_cache');
		$advertiserInfosMap = array();
		if($isRedisConnected_ads && $advertiserKeys){
			$advertiserKeys=array_values(array_unique($advertiserKeys));
			$advertiserInfos=$oRedis_ads->mget($advertiserKeys);
	        if($advertiserInfos){
	          	foreach ($advertiserInfos as $key => $advertiser) {
	          		$advertiser = json_decode($advertiser,true);
	          		$advId = (string)$advertiser['_id'];
	          		$advertiserInfosMap[$advId] = $advertiser;
	          	}
			}
		}

		//每个广告每天对每个用户最大的展示次数
		$userMaxShows = $arrRecommendConfig['dailyMaxShow'];
		if($oReq['ads_group'] == 'article_recommend'){
			$userMaxShows = 2;
		}elseif($oReq['ads_group'] == 'article_bottom_banner'){
			if(stristr($oReq['_udid'], "zk_ads_")){  //wap用户
				$userMaxShows = 1;
			}
		}

		$matchedAds = array();
		$adsRet = array();
		foreach ($adsInfoTmp as $oneAdsDef){
			$oneAdsId = strval($oneAdsDef['_id']);
			$campaignId = strval($oneAdsDef['ad_group_id']);
			//广告计划的曝光次数和点击次数
			if(!empty($oneAdsDef['ad_group_id']) && $arrCampaignClicks[$oneAdsDef['ad_group_id']] > 0){
				$campaignClicks = intval($arrCampaignClicks[$oneAdsDef['ad_group_id']]);
				$campaignShows = intval($arrCampaignShows[$oneAdsDef['ad_group_id']]);
			}else{
				$campaignClicks = intval($arrAdsClicks[$oneAdsId]);
				$campaignShows = intval($arrAdsShows[$oneAdsId]);
			}
			//用户今天点过该广告计划的次数
			$userTodayClicks = 0;
			//用户今天看过该广告计划的次数
			$userTodayShows = 0;
			if(!empty($oneAdsDef['ad_group_id'])){
				$adsKey = $oneAdsDef['ad_group_id'].'_'.$today;
				$userTodayClicks = intval($arrUserCampaignClicks[$adsKey]);
				$userTodayShows = intval($arrUserCampaignShows[$adsKey]);
			}
			//用户总共看过该广告计划的次数
			$userTotalShows = intval($arrUserCampaignShows[$campaignId]);

			$userProductShows = 0;
			if(!empty($oneAdsDef['product'])){
				$advKey = $oneAdsDef['aid'].'_'.$oneAdsDef['product'];
				$userProductShows = intval($productShows[$advKey]);
			}

			//广告计划今天点击数
			$nDailyClicks = intval($arrCampaignClicks[$oneAdsDef['ad_group_id']."_".$today]);
			//广告计划当前小时的点击数
			$currentHourClicks = intval($arrCampaignClicks[$oneAdsDef['ad_group_id']."_".$currentHour]);
			//广告计划上一小时的点击数
			$previousHourClicks = intval($arrCampaignClicks[$oneAdsDef['ad_group_id']."_".$previousHour]);

			//广告计划今天曝光数
			$nDailyShows = intval($arrCampaignShows[$oneAdsDef['ad_group_id']."_".$today]);
			//广告计划当前小时的曝光数
			$currentHourShows = intval($arrCampaignShows[$oneAdsDef['ad_group_id']."_".$currentHour]);
			//广告计划上一小时的曝光数
			$previousHourShows = intval($arrCampaignShows[$oneAdsDef['ad_group_id']."_".$previousHour]);
	     
	        $oneAdsDef['daily_target_views'] = (int)$oneAdsDef['daily_target_views'];
	        $oneAdsDef['daily_target_clicks'] = (int)$oneAdsDef['daily_target_clicks'];
	        $oneAdsDef['clickRatio'] = empty($arrAdsShows[$oneAdsId])? 0: round($arrAdsClicks[$oneAdsId]/$arrAdsShows[$oneAdsId], 4); 
			
			$advertierInfo = $advertiserInfosMap[$oneAdsDef['aid']];

			//合作方渠道投放量最大比例
			if(empty($oneAdsDef['app_partners_ratio'])){
				$partnerExpendRatio = ($oReq['device_id'] == "default") ? 1: 0.5;
			}else{
				$partnerExpendRatio = $oneAdsDef['app_partners_ratio'][$oReq['device_id']];
				if(!$partnerExpendRatio){
					$partnerExpendRatio = 0.1;
				}
			}
			//广告计划在合作方总的点击数
			$partnerTotalClicks = intval($adsPartnerClicks[$oneAdsDef['ad_group_id']]);
			//广告计划在合作方当天的点击数
			$partnerDailyClicks = intval($adsPartnerClicks[$oneAdsDef['ad_group_id']."_".$today]);


			//当天该广告有几个小时允许投放
			$totalHoursCount = 0;
			if( is_array($oneAdsDef['deliver_time_week']) && !empty($oneAdsDef['deliver_time_week']) ) {
				foreach ($oneAdsDef['deliver_time_week'] as $nHour) {
					list($weekDay, $hour) = explode('-',$nHour);
					if($weekDay == $nowWeek){
						$totalHoursCount += 1;
					}
				}
			}
			elseif(is_array($oneAdsDef['deliver_time']) && !empty($oneAdsDef['deliver_time'])){
				$totalHoursCount = count($oneAdsDef['deliver_time']);
			}

            //历史定向lbs过滤
            if (!empty($oneAdsDef['lbs_type']) && $oneAdsDef['lbs_type'] == 2) {
                if (empty($udidLocations)) {
                    $unfitAds['no_lbs_location_id'][] = $adId;
                    continue;
                }

                //读取地标数据，然后与用户常去的locationIds交集
                $adLocationIds = array();
                foreach ($oneAdsDef['lbs_location'] as $item) {
                    if (isset($item['location_id'])) {
                        $adLocationIds[] = $item['location_id'];
                    }
                }

                if (!array_intersect($udidLocations, $adLocationIds)) {
                    $unfitAds['unmatch_lbs_location_id'][] = $adId;
                    continue;
                }
            }

			if(!$advertierInfo){
				$unfitAds['no_advertiser'][] = $oneAdsId;
				continue;
			}
			//D类级别广告主的广告不能出现在高级频道 和热点推荐里
			if($oReq['device_id'] == 'default' && isset($advertierInfo['grade']) && strtoupper($advertierInfo['grade']) == 'D'){
				if(in_array($oReq['app_id'], $superiorBlocks)){
					$unfitAds['unmatch_advertiser_grade'][] = $oneAdsId;
					continue;
				}
				if($oReq['ads_group'] == 'article_recommend'){
					$unfitAds['unmatch_advertiser_grade'][] = $oneAdsId;
					continue;
				}
				//D类级别广告主的三图广告不出现在APP文章底部
                elseif($oReq['ads_group'] == 'article_bottom_banner' && $oneAdsDef['ads_type'] == 15){
                    $unfitAds['unmatch_advertiser_grade'][] = $oneAdsId;
                    continue;
                }
			}
            elseif($oReq['device_id'] == 'default' && isset($advertierInfo['grade']) && strtoupper($advertierInfo['grade']) == 'F'){
            	//F类级别广告主的大图广告不出现在APP/WAP文章底部
                if(($oReq['ads_group'] == 'article_bottom_banner' || $oReq['ads_group'] == 'wap_bottom_banner') && $oneAdsDef['ads_type'] == 1){
                    $unfitAds['unmatch_advertiser_grade'][] = $oneAdsId;
                    continue;
                }
                //F类级别广告主的三图广告不出现在热点推荐
                elseif($oReq['ads_group'] == 'article_recommend' && $oneAdsDef['ads_type'] == 15){
                    $unfitAds['unmatch_advertiser_grade'][] = $oneAdsId;
                    continue;
                }
            }

            //屏蔽酷狗直播乔月
            if ($oReq['ads_group'] == 'article_recommend' && $oneAdsDef['aid'] == '5b715567b09efe1756000027') {
                continue;
            }

            //排除掉点击过的广告组的广告（导量渠道不需要排除广告）
            if($oneAdsDef['deliver_type']==1 && !$is_white_list_user && !in_array($oReq['device_id'], $outward_channels) ){
                if(is_array($arrUserAdPackageClicks) && in_array($oneAdsDef['packageid'], $arrUserAdPackageClicks)) {
                    $unfitAds['has_clicked_ad_package'][] = $oneAdsId;
                    continue;
                }
            }
			//排除掉用户当天看过多次的广告
			if(!$is_white_list_user && $userTodayShows >= $userMaxShows){
				$unfitAds['exceed_today_user_max_shows'][] = $oneAdsId;
				continue;
			}
			//排除掉活跃用户总共看过10次的广告
			if($isActiveUser && $userTotalShows >= 10){
				$unfitAds['exceed_user_total_max_shows'][] = $oneAdsId;
				continue;
			}

			//排除掉用户看过多次的相同产品的广告
			if($userProductShows >= $productMaxShowCount){
				$unfitAds['exceed_product_max_shows'][] = $oneAdsId;
				continue;
			}

			if( $oneAdsDef['deliver_type']==1 ){
				$oneAdsDef['finishedRatio'] = round($campaignClicks/$oneAdsDef['target_clicks'], 3);
				/*
				$adsGroupMinRatio = floatval($adsGroupCtr[$oReq['ads_group']]) * $arrRecommendConfig['adsGroupClickRatioDiscount'];
				foreach ($oneAdsDef['creatives'] as $key=>$creative) {
					$cid = $creative['_id'];
					$creativeClickRatio = $creativeShows[$cid] ? round($creativeClicks[$cid]/$creativeShows[$cid], 4) : 0;
					//如果创意的点击率小于广告位点击率的最小值，则删除掉这个创意
					if($creativeShows[$cid] > $arrRecommendConfig['adsGroupBaseShow'] && $creativeClickRatio < $adsGroupMinRatio){
						unset($oneAdsDef['creatives'][$key]);
					}
				}
				//没有合格的广告创意，就过滤掉这个广告
				if(empty($oneAdsDef['creatives'])){
					$unfitAds['no_qualified_creative'][] = $oneAdsId;
					continue;
				}else{
					$oneAdsDef['creatives'] = array_values($oneAdsDef['creatives']);
				}
				*/

				//排除掉用户当天已点击的广告
				if(!$is_white_list_user && $userTodayClicks >= $arrRecommendConfig['dailyMaxClick']){
					$unfitAds['exceed_today_user_max_clicks'][] = $oneAdsId;
					continue;
				}
				//排除广告主余额不足（少于2元）的广告
				if(($advertierInfo['balance'] -2*100) < $oneAdsDef['prize_weight']*100){
					$unfitAds['no_balance'][] = $oneAdsId;
					continue;
				}
				//排除达到日预算目标的广告
				if($oneAdsDef['daily_target_clicks'] > 0 && $nDailyClicks >= $oneAdsDef['daily_target_clicks']){ 
					$unfitAds['exceed_daily_target_clicks'][] = $oneAdsId;
					continue;
				}
				//排除达到总预算目标的广告
				if($campaignClicks >= $oneAdsDef['target_clicks']){
					$unfitAds['exceed_total_target_clicks'][] = $oneAdsId;	
					continue;
				}

				//排除达到合作方当天投放量上限的广告,排除掉刷量渠道
				if( !in_array($oReq['device_id'], $outward_channels) && $oneAdsDef['daily_target_clicks'] > 0 && $partnerDailyClicks >= $oneAdsDef['daily_target_clicks'] * $partnerExpendRatio ){
					$unfitAds['exceed_partner_daily_target_clicks'][] = $oneAdsId;
					continue;
				}
				//排除达到合作方总投放量上限的广告
				if( !in_array($oReq['device_id'], $outward_channels) && $partnerTotalClicks >= $oneAdsDef['target_clicks'] * $partnerExpendRatio ){
					$unfitAds['exceed_partner_total_target_clicks'][] = $oneAdsId;	
					continue;
				}


				if($oneAdsDef['daily_target_clicks'] > 0){
					$budgetAmount = $oneAdsDef['daily_target_clicks'];
				}else{
					$budgetAmount = $oneAdsDef['target_clicks'];
				}
				$previousHourCostAmount = $previousHourClicks;
				$currentHourCostAmount = $currentHourClicks;
			}
			elseif( $oneAdsDef['deliver_type']==2 ){
				$oneAdsDef['finishedRatio'] = round($campaignShows/$oneAdsDef['target_views'], 3);
				//排除广告主余额不足（少于5元）的广告
				if( ($advertierInfo['balance'] -5*100) < intval($oneAdsDef['prize_weight']*100/1000) ){
					$unfitAds['no_balance'][] = $oneAdsId;
					continue;
				}
				//排除达到日预算目标的广告
				if($oneAdsDef['daily_target_views'] > 0 && $nDailyShows >= $oneAdsDef['daily_target_views']){
					$unfitAds['exceed_daily_target_shows'][] = $oneAdsId;
				 	continue;
				}
				//排除达到总预算目标的广告
				if( $campaignShows >= $oneAdsDef['target_views']){
					$unfitAds['exceed_total_target_shows'][] = $oneAdsId;		
					continue;
				}

				if($oneAdsDef['daily_target_views'] > 0){
					$budgetAmount = $oneAdsDef['daily_target_views'];
				}else{
					$budgetAmount = $oneAdsDef['target_views'];
				}
				$previousHourCostAmount = $previousHourShows;
				$currentHourCostAmount = $currentHourShows;
			}

			//每小时匀速投放时，需要对预算量做控制
			if($oneAdsDef['deliver_speed_type'] == 1 && $totalHoursCount > 0 && $budgetAmount > 0){
				$perHourAvgBudget = round($budgetAmount/$totalHoursCount);
				//通过上一小时的消耗量，来确定当前小时的预算量
				if($previousHourCostAmount < $perHourAvgBudget){
					$currentHourBudget = $perHourAvgBudget*1.5;
				}else{
					$currentHourBudget = $perHourAvgBudget*2;
				}
				//如果当前小时的消耗量已经超过当前小时的预算量，把这个广告过滤掉
				if($currentHourCostAmount >= $currentHourBudget){
					$unfitAds['exceed_hourly_target_amount'][] = $oneAdsId;		
					continue;
				}
			}

			$oneAdsDef['show_count'] = intval($arrAdsShows[$oneAdsId]);
			$oneAdsDef['click_count'] = intval($arrAdsClicks[$oneAdsId]);
			$oneAdsDef['userShows'] = intval($arrUserCampaignShows[$campaignId]);
			$oneAdsDef['userShowsToday'] = intval($arrUserCampaignShows[$campaignId.'_'.$today]);
			$oneAdsDef['userClicks'] = intval($arrUserCampaignClicks[$campaignId]);
			$oneAdsDef['campaign_show_count'] = $campaignShows;
			$oneAdsDef['campaign_click_count'] = $campaignClicks;

			$matchedAds[] = $oneAdsId;
			$adsRet[$oneAdsId] = $oneAdsDef;
		}

		//如果无广告返回，记录原因和次数
		if(empty($adsRet)){
			$unfitReason = array_keys($unfitAds);
			$unfitReasonByUser = array("has_clicked_ad_package", "exceed_today_user_max_shows", "exceed_user_total_max_shows", "exceed_product_max_shows", "exceed_today_user_max_clicks");

			$unfitReasonByBudget = array("exceed_daily_target_clicks", "exceed_total_target_clicks", "exceed_daily_target_shows", "exceed_total_target_shows", "exceed_hourly_target_amount");

			if(array_intersect($unfitReason, $unfitReasonByUser)){   
				$no_ads_reason = "user_frequency_control";
			}elseif(array_intersect($unfitReason, $unfitReasonByBudget)){
				$no_ads_reason = "ads_budget_control";
			}else{
				$no_ads_reason = "ads_setting";
			}
			zk_ads_record_no_ads_reason($currentHour, $oReq, $no_ads_reason);
		
		}elseif(count($adsRet) <= 2){ //只有1个或两个广告
			$adsRetTmp = $adsRet;
			$adsRet = $matchedAds = array();
			foreach ($adsRetTmp as $oneAdsId => $oneAdsDef) {
				$campaignId = $oneAdsDef['ad_group_id'];
				$userTodayShows = intval($arrUserCampaignShows[$campaignId.'_'.$today]);
				//如果今天用户已看过两次，就过滤掉
				if($userTodayShows >= 2){
					$unfitAds['exceed_today_user_max_shows'][] = $oneAdsId;
					continue;
				}
				$matchedAds[] = $oneAdsId;
				$adsRet[$oneAdsId] = $oneAdsDef;
			}
		}

		zk_ads_add_log($unfitAds, 'unfit_ads');
		zk_ads_add_log($matchedAds, 'matched_ads');

		return $adsRet;
	}
}

if(!function_exists('zk_ads_find_ad')){
	/**
	 * 查找用户要看的广告
	 * @param string $ads_group
	 * @param string $oReq
	 * @return mixed
	 */
	function zk_ads_find_ad($ads_group, &$oReq){
		if(ZK_ADS_IS_SHOW_ADS == 0){
 			return array(null, array(), array());
 		}
	    
		$oReq['ads_group'] = $ads_group;
		$nowDayHour = date("Y-m-d_H");

		//记录广告请求次数
		zk_ads_cache_channel_request_count_incr($oReq['device_id'], $ads_group);
		if($oReq['device_id'] == 'default'){  //记录ZAKER客户端每小时请求数
			zk_ads_cache_channel_request_count_incr($oReq['device_id'], $ads_group, 1, $nowDayHour);
		}

		//判断广告权限
		if(!zk_ads_check_ad_permission($oReq)){
			zk_ads_add_log('no permission', 'NO_ADS_REASON');
			zk_ads_record_no_ads_reason($nowDayHour, $oReq, 'channel_forbid_ads');
			return array(null,array(),array());
		}
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		//热点置顶文章底部和精彩推荐文章不出广告
		if( $isRedisConnected && in_array($oReq['ads_group'], array("article_bottom_banner","article_jingcai")) ){
			//获取热点置顶文章ID
			$topArticleIds = $oRedis->sMembers(ZK_ADS_TOP_ARTICLE_IDS);
			if(in_array($oReq['pk'], $topArticleIds)) {
				zk_ads_add_log('top article remove ads', 'NO_ADS_REASON');
				return array(null,array(),array());
			}
		}

		/*
		//判断是请求合作方ADX的广告，还是自有DSP的广告
		if($oReq['device_id'] == 'default' && $oReq['_version'] >= 7.94 && $oReq['app_id'] != 660 && in_array($oReq['ads_group'], array('article_bottom_banner', 'article_recommend', 'article_jingcai')) ){
			$tanxRequest = partner_ads_get_today_tanx_ad_request_count();
			//当淘宝广告的请求量少于500万时，55%的流量给合作方广告
			if($tanxRequest < 5000000){
				$adsRet = zk_ads_get_ads_from_partner_platform($oReq, false);
				//var_dump($adsRet);exit;
				if($adsRet && !empty($adsRet[0])){
					return $adsRet;
				}
			}
		}
		*/
		if($oReq['device_id'] == 'default' && $oReq['_version'] >= 7.94 && $oReq['ads_group'] == "article_bottom_banner"){
			$random = rand(0,99);
			if($random < 50){
				$adsRet = zk_ads_get_ads_from_partner_adx_go($oReq, false);
				zk_ads_add_log($adsRet, 'PARTNER_ADS_RET');
				if($adsRet && !empty($adsRet[0])){
					return $adsRet;
				}
			}
		}

		/**
		 * 关键字CPM
		 */
		// 有cpm类型的ads_group
		$cpmAdsGroup = array(
			'article_bottom_banner',
			'block_page'
		);
		$arrKeywordAdsId = array();
		if(in_array($ads_group, $cpmAdsGroup)) {
			//是否有关键字cpm广告
			$keywordResult = zk_ads_check_keyword_cpm($oReq);
			// 如果是返回false，继续执行之前的逻辑
			if($keywordResult){
				// 频道列表页的广告接口需要返回所有数据(接口再出前10条)，所以需要合并关键字cpm广告和旧逻辑广告（先排重）一起返回
				if($ads_group=='block_page'){
					$arrKeywordAdsScore=$keywordResult[1];
					if(count($arrKeywordAdsScore) > 0) {
						foreach($arrKeywordAdsScore as $value) {
							$arrKeywordAdsId[] = $value['ads_id']; //中了CPM关键字的广告id，后面正常逻辑的广告id里面需要排除掉
						}
					}

				}else{
					return $keywordResult; // 文章正文广告则直接返回
				}
			}
		}


		$disableCpmBlock = zk_ads_config('disable_cpm_ad_block');
		
		$arrRecommendConfig = zk_ads_config('recommend_config');

		$special_advertisers = zk_ads_config('special_brand_advertisers');  //特殊品牌广告主
		
		//获取用户喜爱的分类,权重
		list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
		$oReq['sex'] = $sUserGender;

		//测试分类
		if($oReq['_user_category']){
			$arrTestFavTypes = explode(",", $oReq['_user_category']);
			if(is_array($arrTestFavTypes) && count($arrTestFavTypes) > 0){
				$arrUserFavTypeIds = $arrTestFavTypes;
				$arrUserFavTypeWeight=array();
				foreach ($arrUserFavTypeIds as $key => $value) {
					$arrUserFavTypeWeight[$value]=20;
				}
			}
		}
		 
        //如果没有喜好分类,默认加上科技,财经,汽车,时尚
	    if(!$arrUserFavTypeIds){
           $arrUserFavTypeIds=array(1,3,5,9);
           $arrUserFavTypeWeight=array(1=>20,3=>20,5=>20,9=>20);
           	//合作方没有喜好分类，默认加上所有喜好分类
	       	if(!empty($oReq['device_id']) && $oReq['device_id'] != 'default'){
	        	for($i=1; $i<21; $i++){
	        		$arrUserFavTypeIds[] = $i;
	        		$arrUserFavTypeWeight[$i] = 10;
	        	}
	        } 	  
	    }
	    $whiteListUdids = zk_ads_config('special_brand_white_list');
	    if(in_array($oReq['_udid'], $whiteListUdids)){
	    	for($i=1; $i<21; $i++){
	        	$arrUserFavTypeIds[] = $i;
	        	$arrUserFavTypeWeight[$i] = 10;
	        }
	    }

		//获取用户的标签,权重
		list($arrUserTags,$arrUserTagWeight) = zk_ads_get_user_tags($oReq['_udid']);
		
		//测试标签
		if($oReq['_user_tag']){
			$arrTestTags = explode(",", $oReq['_user_tag']);
			if(is_array($arrTestTags) && count($arrTestTags) > 0){
				$arrUserTags = $arrTestTags;
			}
		}
			
		//获取文章信息
		$article = zk_ads_get_article($oReq['pk']);
		zk_ads_add_log($article['new_tag'], 'ARTICLE_TAG');
		
		//没有文章信息时,获取频道信息
		if(empty($article)){
			$article = array(
					'new_app_id' => 1000,
					'new_tag' => array(),
			);
			$appinfo = zk_ads_get_appinfo($oReq['app_id']);
		}
		
		$arrAdsIds = array();
		
		//有传入pk时,加入文章大类
		if($article['new_app_id'] > 0){
			array_push($arrUserFavTypeIds, $article['new_app_id']);
			$arrUserFavTypeWeight[$article['new_app_id']] += $arrRecommendConfig['aFavWeight'];
			$oReq['strict_category_id'] = $article['new_app_id'];
			$oReq['new_app_id'] = $article['new_app_id'];
		}
		
		if($appinfo['category_id'] > 0 && $appinfo['category_id'] <= 1000){
			array_push($arrUserFavTypeIds, $appinfo['category_id']);
			$arrUserFavTypeWeight[$appinfo['category_id']] += $arrRecommendConfig['aFavWeight'];
			if(empty($oReq['strict_category_id'])||$oReq['strict_category_id']==1000){

				$oReq['strict_category_id'] = $appinfo['category_id'];
				$oReq['new_app_id'] = $appinfo['category_id'];
			}
		}
		//测试用
        if($_GET['test_category_id']){
        	$oReq['strict_category_id']=$_GET['test_category_id'];
        }
		$arrUserFavTypeIds = array_values(array_unique($arrUserFavTypeIds));
		
		//测试频道出测试广告
		if($oReq['app_id'] == 862){
			$arrUserFavTypeIds = array(99);
			$arrUserFavTypeWeight = array('99' => 10000);
			//$arrUserFavTypeIds = array(1,2,3,99);
			//$arrUserFavTypeWeight = array('1'=>'10000','2'=>'10000','3'=>'10000','99' => 10000);
		}

		zk_ads_add_log($arrUserFavTypeIds, 'USER_FAV_TYPE_IDS');
		zk_ads_add_log($arrUserFavTypeWeight, 'USER_FAV_TYPE_WEIGHT');
		
		
		
		//获取喜好分类对应的广告IDS
		$arrUserFavAdsIds = zk_ads_get_fav_ads_ids($arrUserFavTypeIds,$oReq);
		
		zk_ads_add_log($arrUserFavAdsIds, 'USER_FAV_ADS_IDS');
		
		//加入文章标签
		if(is_array($article['new_tag']) && count($article['new_tag']) > 0){
			$oReq['article_tags'] = $article['new_tag'];
			$arrUserTags = array_merge($arrUserTags,$article['new_tag']);
			$arrUserTags = array_values(array_unique($arrUserTags));
			foreach ($article['new_tag'] as $oneTag){
				$arrUserTagWeight[$oneTag] += $arrRecommendConfig['aTagWeight'];
			}
		}else{
			$oReq['article_tags'] = array();
		}
		$arrUserTags = array_values($arrUserTags);
		
		zk_ads_add_log($arrUserTags, 'USER_TAGS');
		zk_ads_add_log($arrUserTagWeight, 'USER_TAGS_WEIGHT');
		
		//获取标签对应的广告IDS
		$arrUserTagAdsIds = zk_ads_get_tag_ads_ids($arrUserTags,$oReq);
		
		zk_ads_add_log($arrUserTagAdsIds, 'USER_TAG_ADS_IDS');
		
		//获取频道对应的广告IDS
		$arrUserChannelAdsIds = zk_ads_get_channel_ads_ids($oReq['app_id'],$oReq);
		
		zk_ads_add_log($oReq['app_id'], 'USER_CHANNEL');
		zk_ads_add_log($arrUserChannelAdsIds, 'USER_CHANNEL_ADS_IDS');
		
		
		$tmpCity=$oReq['_city'];
		$tmpProvince=$oReq['_province'];
		//获取地域对应的广告IDS
		if($oReq['app_id'] == 862){
			$tmpCity=$oReq['_city'].'_test';
			$tmpProvince=$oReq['_province'].'_test';
		}
		$arrUserLocationAdsIds = zk_ads_get_location_ads_ids(array($tmpCity,$tmpProvince),$oReq);
		
		zk_ads_add_log(array($tmpCity,$tmpProvince), 'USER_LOCATION');
		zk_ads_add_log($arrUserLocationAdsIds, 'USER_LOCATION_ADS_IDS');
		
		
		//合并广告IDS
		$arrAdsIds = array_values(array_unique(array_merge($arrUserFavAdsIds, $arrUserTagAdsIds,$arrUserChannelAdsIds, $arrUserLocationAdsIds)));
		$arrThirdPartyID = zk_ads_config('third_party_id');
		zk_ads_add_log($arrAdsIds, 'MERGE_ADS_IDS');
		
		if(empty($arrAdsIds)){ //没有匹配到广告，直接返回
			$logData = $oReq;
			$logData['status'] = 'no_ads';
			zk_ads_show_data_into_queue($logData); //记录到无广告统计队列
			zk_ads_record_no_ads_reason($nowDayHour, $oReq, 'no_ads_in_cache');
			return array(null,array(),array());
		}

		$adInfos = zk_ads_get_ads_info($arrAdsIds, $oReq);
		//广告筛选过滤
        $arrAdsDetails = zk_ads_common_filter($adInfos, $oReq, $arrRecommendConfig);
		//给每个广告选择合适的创意素材
		$arrAdsDetails = zk_ads_select_creative($arrAdsDetails);

        if($arrAdsDetails){
        	$disabledGifPic = in_array($oReq['ads_group'], array('article_recommend', 'block_page'));
		    foreach ($arrAdsDetails as $key => $value) {
		    	$adId = (string)$value['_id'];

		    	//热点和频道列表禁止输出gif动图广告
		    	if($oReq['device_id'] == "default" && !in_array($value['aid'], $special_advertisers)){
		    		if($disabledGifPic && strstr($value['ads_pic'], ".gif")){
		    			$disabledGifAds[] = $adId;
		    			unset($arrAdsDetails[$key]);
		    			continue;
		    		}
		    	}

		    	//改变广告图片的路径，防止被广告清理插件屏蔽掉
		    	if(!empty($value['ads_pic'])){
		    	    $ads_pic = str_replace('adpic', 'pic1', $value['ads_pic']);
		    	    $arrAdsDetails[$key]['ads_pic'] = zk_ads_change_http_prefix($ads_pic, $oReq['http_type']);
		    	}
		    	if(!empty($value['ads_short_pic'])){
		    	    $ads_short_pic = str_replace('adpic', 'pic1', $value['ads_short_pic']);
		    	    $arrAdsDetails[$key]['ads_short_pic'] = zk_ads_change_http_prefix($ads_short_pic, $oReq['http_type']);
		    	}
		    }
        }
        if($disabledGifAds){
        	zk_ads_add_log($disabledGifAds, 'DISABLED_GIF_ADS');
        }

        //预测点击率
		$arrAdsHitRates = zk_ads_predict_click_ratio($arrAdsDetails, $oReq);
		zk_ads_add_log($arrAdsHitRates, 'ADS_HIT_RATE');

		/**
		 * 推荐广告
		 * 
		*/
		list($recommendedAdID, $arrAdsScore) = zk_ads_recommend($oReq, $arrAdsDetails, $arrRecommendConfig, $arrUserFavTypeIds, $arrUserFavTypeWeight, $arrUserTags, $arrUserTagWeight, $arrAdsHitRates);
		
		zk_ads_add_log($arrAdsScore, 'ADS_SCORE_SORT');
		zk_ads_add_log($recommendedAdID, 'RECOMMEND_ADS_ID');

		//如果是频道文章列表，并且有关键字cpm广告，需要合并结果一起返回
		if($keywordResult && $ads_group=='block_page') {
			list($recommendedAdIDKeyword, $arrAdsScoreKeyword, $arrAdsDetailsKeyword) = $keywordResult;
			return array(
				$recommendedAdIDKeyword,
				array_merge($arrAdsScoreKeyword, $arrAdsScore),
				array_merge($arrAdsDetailsKeyword, $arrAdsDetails)
			);
		}

		if(empty($oReq['mycheering_ad']) && !empty($arrAdsScore)){
			//记录广告响应次数
			zk_ads_cache_channel_response_count_incr($oReq['device_id'], $ads_group);
		}

		/*
		$inmobiTestIdfa = array('76F3464F-E914-40AF-A57F-CA4168CDDAF8', 'EC449871-D9A2-4274-968D-8783CAB6D547');
		if( $oReq['ads_group'] == 'article_bottom_banner' && in_array($oReq['_idfa'], $inmobiTestIdfa) ){
			$arrAdsScore = $arrAdsDetails = array();
			$recommendedAdID = null;
		}
		*/
		//如果没有广告，就从合作方平台获取广告
		if($oReq['device_id'] == 'default' && $oReq['_version'] >= 7.94 && empty($arrAdsScore) ){
			//$recommendedAdID = null;
			//$arrAdsScore = array();
			$partnerAds = zk_ads_get_ads_from_partner_adx_go($oReq, true);

            if($partnerAds){
                list($partnerAdID, $partnerAdsScore, $partnerAdsDetails) = $partnerAds;
                if(!$recommendedAdID){
                    $recommendedAdID = $partnerAdID;
                }
                $arrAdsScore = array_merge($arrAdsScore, $partnerAdsScore);
                $arrAdsDetails = array_merge($arrAdsDetails, $partnerAdsDetails);
                zk_ads_add_log($partnerAdID, 'PARTNER_ADS_RECOMMEND');
            }
		}

		return array($recommendedAdID, $arrAdsScore, $arrAdsDetails);
		
	}

}

if(!function_exists('zk_ads_check_deliver_time_available')) {
	/**
	 * 曝光、点击接口判断是否在广告的投放时间内
	 * 如果无效，记录到异常流量，直接返回
	 * @param $ads
	 * @param $oReq
	 * @param $type
	 * @return bool
	 */
	function zk_ads_check_deliver_time_available($ads, $oReq, $type)
	{
		try{
			$nowHour = intval(date('H'));
			$nowWeek = intval(date('N')); //星期一至星期日 1~7
			$nowWeekToken = $nowWeek.'-'.$nowHour;
			$event_type = 'deliver_time_'.trim($type);
			$isAvailable = true;

			// 星期一到星期天的24小时选择 || 旧的广告时间选择选项
			if( is_array($ads['deliver_time_week']) && !empty($ads['deliver_time_week']) && !in_array($nowWeekToken, $ads['deliver_time_week']) ) {
				$isAvailable = false;
			}elseif( is_array($ads['deliver_time']) && !empty($ads['deliver_time']) && !in_array($nowHour, $ads['deliver_time']) ) {
				$isAvailable = false;
			}

			if(!$isAvailable){

				//入第三方渠道异常统计队列
				$arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
				$arrQueue = array(
					'ads_group' => $ads['ads_group'],
					'ads_id' => strval($ads['_id']),
					'event_type' => $event_type,
					'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
					'device_id' => strval($oReq['device_id']) ,
					'udid' => $oReq['_udid'],
					'dtime' => $oReq['now'],
					'province' => $oReq['_province'],
					'city' => $oReq['_city'],
					'ip' => $oReq['ip'],
					'user_tag' => '',
					'user_category' => '' ,
					'block_pk' => $oReq['app_id'] ? intval($oReq['app_id']) : '',
					'prize_weight' => $ads['prize_weight'],
					'new_app_id' => '',
					'app_version' => $oReq['_version'] ? trim((string)$oReq['_version']) : '',
					'category_first' => strval($ads['category_first']),
					'category_second' => strval($ads['category_second']),
					'category_third' => strval($ads['category_third']),
					'deliver_type' => strval($ads['deliver_type']),
					'cp_app_id'=>$oReq['cp_app_id'] ? $oReq['cp_app_id'] : '',
					'creative_id'=>$oReq['creative_id'],

				);

				if (!empty($ads['ad_group_id'])) {
					$arrQueue['ad_group_id'] = strval($ads['ad_group_id']);
				}

				zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_THIRD_BAD_STAT, $arrQueue);

				//记录错误后直接返回
				if ($ads['third_views_url'] != ''&&$_GET['edebug']!=1) {
					header('Location:' . $ads['third_views_url']);
				}elseif (!empty($oReq['ad_url'])) {
            		header("Location:" . $oReq['ad_url']);
        		}else {
					echo json_encode(array(
						'stat' => -1,
						'msg' => 'invalid time'
					));
				}
				exit;
			}

		}catch (Exception $e) {

		}
		return true;
	}

}

function zk_ads_show_data_into_queue($logData){
	$arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
	$remark = '';
	if($logData['status'] == 'no_ads'){
		$remark = '无可用广告'; 
	}elseif($logData['status'] == 'ads_rotate_finished'){
		$remark = '广告完成一次轮换';
	}
	$arrQueue = array(
		'site_id' => 'dsp',
		'event_id' => $logData['status'],
		'ads_group' => strval($logData['ads_group']),
		'ad_group_id' => strval($logData['ad_group_id']),
		'ads_id' => strval($logData['adID']),
		'item_pk' => strval($logData['pk']),
		'udid' => strval($logData['_udid']),
		'dtime' => time(),
		'province' => strval($logData['_province']),
		'city' => strval($logData['_city']),
		'ip' => strval($logData['ip']),
		'user_tag' => str_replace(',', ' ', $logData['_user_tag']),
		'user_category' => str_replace(',', ' ', $logData['_user_category']),
		'block_pk' => strval($logData['app_id']),
		'new_app_id' => trim((string)$logData['new_app_id']),
		'cp_app_id' => strval($logData['cp_app_id']),
		'app_version' => trim((string)$logData['_version']),
		'device_type' => strval($arrDeviceTypeNameToNum[$logData['_appid']]),
		'device_id' => strval($logData['device_id']),
		'total_num' => strval($logData['total_ads_num']),
		'skip_num' => strval($logData['skip_ads_num']),
		'click_count' => strval($logData['click_count']),
		'remark' => $remark,
	);
	return zk_ads_queue_lpush(ZK_ADS_QUEUE_DSP_SHOW_ADS_STAT, $arrQueue);
}

/**
 * 切换创意
 * 
 * @description 此方法不判断广告点击量是否大于预算点击的10%,查找广告时才判断
 * @param  [type] $oReq       [description]
 * @return boolean            [description]
 */
function zk_ads_creative_change($oReq){
 
	$nNowTime=time();
 	$where = array(
		      'start_time' => array('$lt' => $nNowTime),
		      'end_time' => array('$gt' => $nNowTime),
		      'deliver_type'=>1,//CPC广告
	); 
    $adData = zk_ads_get_def_data($where, array(), array('_id', 'ad_group_id'));

	if(!$adData||!$adData[0]){
		return -1;
	}
	 list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
	if(!$isRedisConnected){
		return -2;
	}
    $showKey=ZK_ADS_CACHE_CREATIVE_SHOW_COUNT;
    $clickKey=ZK_ADS_CACHE_CREATIVE_CLICK_COUNT;
    $showCacheKeys=$clickCacheKeys=array();
    $creativeIdMap=array();
    $twoDayAgo=date('Y-m-d',time()-86400*2);
    $oneDayAgo=date('Y-m-d',time()-86400); 
    $today=date('Y-m-d');
    $adIds=array();
    $campaignIds = array();
    foreach ($adData as $key => $value) {
    	$tmpAdId=(string)$value['_id'];
    	$campaignId = $value['ad_group_id'];
    	if(!in_array($campaignId, $campaignIds)){
    		$campaignIds[] = $campaignId;
    		$adIds[]=ZK_ADS_CACHE_SINGLE_ADS_DEF.$tmpAdId;
    	}
    } 
    unset($adData);
    $adCache=$oRedis->mGet($adIds);

    if(!$adCache){
    	return -3;
    } 
    $campaignCreativeCounts=array();
    $adDetail =array();
    foreach ($adCache as $adKey=>$ad) {
        if(!$ad){
        	continue;
        } 
        $ad=json_decode($ad,true);
        
        if(!$ad['creatives']){
        	continue;
        }
        $adDetail[$ad['ad_group_id']]=$ad;
        foreach ($ad['creatives'] as $key => $creative) {
        	//记录每个campaign的创意数量
            $adCreativeCounts[$ad['ad_group_id']]++;
            //获取三天的曝光点击
        	$showCacheKeys[]= zk_ads_cache_get_key($showKey.$creative['_id'].'_'.$today, 'cs_', 16);
        	$showCacheKeys[]= zk_ads_cache_get_key($showKey.$creative['_id'].'_'.$oneDayAgo, 'cs_', 16);
        	$showCacheKeys[]= zk_ads_cache_get_key($showKey.$creative['_id'].'_'.$twoDayAgo, 'cs_', 16);
           
        	$clickCacheKeys[]= zk_ads_cache_get_key($clickKey.$creative['_id'].'_'.$today, 'cc_', 16);
        	$clickCacheKeys[]= zk_ads_cache_get_key($clickKey.$creative['_id'].'_'.$oneDayAgo, 'cc_', 16);
        	$clickCacheKeys[]= zk_ads_cache_get_key($clickKey.$creative['_id'].'_'.$twoDayAgo, 'cc_', 16);
    
        	$creativeIdMap[]=$creative['_id'];
            $creativeIdMap[]=$creative['_id'];
            $creativeIdMap[]=$creative['_id'];
        }
   
    } 
	unset($adCache);

    if(!$adCreativeCounts){
    	return -4;
    }
    list($oRedis_ads, $isRedisConnected_ads) = zk_ads_redis('ads_cache');
    if(!$isRedisConnected_ads){
    	return -5;
    }
    $showCounts=$oRedis_ads->mGet($showCacheKeys);
    $clickCounts=$oRedis_ads->mGet($clickCacheKeys);
    //点击率权重:今天,昨天,前天占比
    $weights=array(0.5,0.4,0.1);
    //计算各个创意的点击率点击率
    $ctrWeightMap=array();   
    $ctrWeightMapLog=array();                                          
    $i=0;
    $winArr=array();
    // print_r($showCounts);
    // print_r($clickCounts);
    // print_r($adCreativeCounts);
    $updateData=array();

    foreach ($adCreativeCounts as $campaignId=>$count) {
    	//每个广告计划拿三天的曝光,点击数据
       $tmpTotal=$i+$count*3;
       for($i;$i<$tmpTotal;$i++){ 
         
          //新加的创意给定0.3%的点击率
          $ctrWeightMap[$campaignId][$creativeIdMap[$i]]+=($showCounts[$i]>0?$clickCounts[$i]/$showCounts[$i]:0.003)*$weights[$i%3];
          $ctrWeightMapLog[$campaignId][$creativeIdMap[$i]].=($showCounts[$i]>0?$clickCounts[$i]/$showCounts[$i]:0.003).'*'.$weights[$i%3].'+';
       }
       //按得分排序
       arsort($ctrWeightMap[$campaignId]);
       //print_r($ctrWeightMapLog[$campaignId]);
       $tmpKeys=array_keys($ctrWeightMap[$campaignId]);
       //胜出的创意
       $winId=$tmpKeys[0];
       $winArr[$campaignId]=$winId;       
           
       //相同就不重新设置了
       if(!isset($adDetail[$campaignId]['creatives'][$winId])){
          continue;
       }
       $cacheKey=ZK_ADS_CACHE_CAMPAIGN_CREATIVE.$campaignId;
       $title = empty($adDetail[$campaignId]['creatives'][$winId]['title']) ? "": $adDetail[$campaignId]['creatives'][$winId]['title'];
       $adsPic = empty($adDetail[$campaignId]['creatives'][$winId]['ads_pic']) ? "": $adDetail[$campaignId]['creatives'][$winId]['ads_pic'];
       $adsShortPic = empty($adDetail[$campaignId]['creatives'][$winId]['ads_short_pic']) ? "": $adDetail[$campaignId]['creatives'][$winId]['ads_short_pic'];
       $multiPics = empty($adDetail[$campaignId]['creatives'][$winId]['multi_pics']) ? "": $adDetail[$campaignId]['creatives'][$winId]['multi_pics'];
       $cacheData=array(
       		'id'=>$winId,
       		'title' => $title,
       		'ads_pic'=> $adsPic,
       		'ads_short_pic'=> $adsShortPic,
       		'multi_pics' => $multiPics,
       		'logs'=>$ctrWeightMapLog[$campaignId]
       	); 
 	   zk_ads_add_log($cacheData, 'CREATIVE_DATA');
   
       $oRedis->setex($cacheKey,ZK_ADS_ADS_CACHE_EXPIRE,json_encode($cacheData));

    }
     
    return $winArr;
}

if(!function_exists('zk_ads_article_recommend_ad')){
	/**
	 * 查找推荐列表的广告
	 * @param string $oReq
	 * @return mixed
	 */
	function zk_ads_article_recommend_ad($oReq){
		
		$oReq['ads_group'] = 'article_recommend';
		
		list($recommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('article_recommend',$oReq);
	 	
	 	$oneAds = array();
	 	$filtered = array();
	 	if(!empty($arrAdsScore)){

	 		//不和上一次请求的广告相同
			$lastAdvertiser = zk_ads_get_user_recommand_last_advertiser($oReq['_udid']);
			zk_ads_add_log($lastAdvertiser, 'REC_LAST_ADVERTISER');

			if($lastAdvertiser) {

				foreach ($arrAdsScore as $key => $value) {
					if(!$lastAdvertiser || $value['aid'] != $lastAdvertiser){
						$recommendAdsID = $value['ads_id'];

						//记录这一次的广告
						zk_ads_set_user_recommand_last_advertiser($oReq['_udid'], $value['aid']);
						zk_ads_add_log($value['aid'], 'REC_RECOMMEND_ADVERTISER');
						
						unset($GLOBALS['ZK_ads_logs']['RECOMMEND_ADS_ID']);
						zk_ads_add_log($recommendAdsID, 'RECOMMEND_ADS_ID');
						break;
					}
				}

			} else {

				zk_ads_set_user_recommand_last_advertiser($oReq['_udid'], $recommendAdsID);

			}


	 		$oneAds = $arrAdsDetails[$recommendAdsID];
	 	}

		if(!empty($oneAds)){
			$adsArticle = zk_ads_format_article_recommend($oneAds,$oReq);
			$adsArticle['aid'] = $oneAds['aid'];
			$adsArticle['channel_positions'] = $oneAds['channel_positions'];
			if($_REQUEST['_version'] >= 6.6 ){
				return $adsArticle;
			}
			//统计广告曝光
			zk_ads_cache_ads_show_count_incr(strval($oneAds['_id']));
			//统计用户看过的广告
			zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($oneAds['_id']), 1, ZK_ADS_USER_ADS_RECOMMEND_SHOW_CACHE_EXPIRE);
			//统计用户今天看过的广告
            zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($oneAds['_id']).'_'.date('Y-m-d'),1,strtotime(date('Y-m-d',strtotime('+1 day')))-time());
            //读取用户喜爱的分类,权重
            list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
            	
            //入动作统计队列
            $arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
            $arrQueue = array(
            		'ads_group' => $oneAds['ads_group'],
            		'ads_id' => strval($oneAds['_id']),
            		'event_type' => 'show',
            		'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
            		'device_id' => strval($oReq['device_id']),
            		'udid' => $oReq['_udid'],
            		'dtime' => $oReq['now'],
            		'province' => $oReq['_province'],
            		'city' => $oReq['_city'],
            		'ip' => $oReq['ip'],
            		'user_tag' => '',
            		'user_category' => implode(" ", $arrUserFavTypeIds),
            		'block_pk' => intval($oReq['app_id']),
            		'prize_weight' => $oneAds['prize_weight'],
            		'ad_info'=>$oneAds,
            		'deliver_type' => strval($oneAds['deliver_type']),
            		'cp_app_id'=>$oReq['cp_app_id'],
            		'creative_id'=>$oReq['creative_id'],
            		//新增统计参数
            		'new_app_id' => trim((string)$oReq['new_app_id']),
            		'app_version' => trim((string)$oReq['_version']),
            		'category_first' => strval($oneAds['category_first']),
            		'category_second' => strval($oneAds['category_second']),
            		'category_third' => strval($oneAds['category_third']),
            );
            	
            if(!empty($oneAds['ad_group_id'])){
            	$arrQueue['ad_group_id'] = strval($oneAds['ad_group_id']);
            }
            	
            zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_STAT, $arrQueue);
            
            
			//统计广告组点击
			if(!empty($oneAds['ad_group_id'])){
				zk_ads_cache_ads_show_count_incr($oneAds['ad_group_id']);
				zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], $oneAds['ad_group_id']);
			}
			
			if(ZK_ADS_IS_SHOW_ADS == 0){
				return null;
			}
			
			return $adsArticle;
		}
		
		return null;
	}
}

if(!function_exists('zk_ads_block_ad')){
	/**
	 * 查找频道列表的广告
	 * @param string $oReq
	 * @return mixed
	 */
	function zk_ads_block_ad($oReq,$nt,$size = null){
		$arrAdsArticles = array();
		zk_ads_add_log($oReq, 'REQUEST');
		$oReq['ads_group'] = 'block_page';
		
		list($recommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('block_page',$oReq);
		
		//第一批出1条，其余出3条
		if($nt == 0){
			if($size > 0){
				$adnum = $size;
			}else{
				$adnum = 1;
			}
			$j = 0;
		}else{
			if($size > 0){
				$adnum = $size;
				$j = $adnum+($nt-1)*$adnum;
			}else{
				$adnum = 3;
				$j = 1+($nt-1)*$adnum;
			}
		}
// 		$adnum = zk_ads_config('block_ad_num');
		
		//读取用户喜爱的分类,权重
		list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
		
		for ($i=0;$i<$adnum;$i++){
			$oneAds = $arrAdsDetails[$arrAdsScore[$j+$i]['ads_id']];
			if(!empty($oneAds) && $oneAds['deliver_type'] != 2){
				$arrAdsArticles[] = zk_ads_format_article_block($oneAds,$oReq);
				$ad_count += 1;
				//2015-08-12加上判断,修复之前几个版本曝光数重复统计了
				if($oReq['_version']<6.1){
                   
				   //统计广告曝光
				   zk_ads_cache_ads_show_count_incr(strval($oneAds['_id']), 1);
				   //此方法没有传_udid
				   // //统计用户看过的广告
       //             zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($oneAds['_id']));
				   // //统计用户今天看过的广告
       //             zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($oneAds['_id']).'_'.date('Y-m-d'));
				   //入动作统计队列
				   $arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
				   $arrQueue = array(
				   		'ads_group' => $oneAds['ads_group'],
				   		'ads_id' => strval($oneAds['_id']),
				   		'event_type' => 'show',
				   		'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
				   		'device_id' => strval($oReq['device_id']),
				   		'udid' => $oReq['_udid'],
				   		'dtime' => $oReq['now'],
				   		'province' => $oReq['_province'],
				   		'city' => $oReq['_city'],
				   		'ip' => $oReq['ip'],
				   		'user_tag' => '',
				   		'user_category' => implode(" ", $arrUserFavTypeIds),
				   		'block_pk' => intval($oReq['app_id']),
				   		'prize_weight' => $oneAds['prize_weight'],
				   		'ad_info'=>$oneAds,
				   		'deliver_type' => strval($oneAds['deliver_type']),
				   		'cp_app_id'=>$oReq['cp_app_id'],
                        'creative_id'=>$oReq['creative_id'],
				   		//新增统计参数
				   		'new_app_id' => trim((string)$oReq['new_app_id']),
				   		'app_version' => trim((string)$oReq['_version']),
				   		'category_first' => strval($oneAds['category_first']),
				   		'category_second' => strval($oneAds['category_second']),
				   		'category_third' => strval($oneAds['category_third']),
				   );
				   	
				   if(!empty($oneAds['ad_group_id'])){
				   	$arrQueue['ad_group_id'] = strval($oneAds['ad_group_id']);
				   }
				   	
				   zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_STAT, $arrQueue);
				   	
				   //统计广告组点击
				   if(!empty($oneAds['ad_group_id'])){
				   	zk_ads_cache_ads_show_count_incr($oneAds['ad_group_id']);
				   	zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], $oneAds['ad_group_id']);
				   }
			    }
		    }
		}
		
		if(ZK_ADS_IS_SHOW_ADS == 0){
			return array();
		}
		
		return $arrAdsArticles;
	}
}


 if(!function_exists('zk_ads_block_ad_2')){
	/**
	 * 查找频道列表的广告
	 * @param string $oReq
	 * @return mixed
	 */
 	function zk_ads_block_ad_2($oReq,$currentPage,$pageSize=10){
 	
 		$arrAds = array();
 		zk_ads_add_log($oReq, 'REQUEST');
 		$oReq['ads_group'] = 'block_page';
 		if(ZK_ADS_IS_SHOW_ADS == 0){
 			return array();
 		}
 		list($recommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('block_page',$oReq);
 			
 		//出10条,不分页
 		$currentPage=1;
 		$j = 1+($currentPage-1)*$pageSize;
 	
 		if(empty($arrAdsScore)){
 			return array();
 		}

 		foreach ($arrAdsScore as $ad){
 			$adsID = $ad['ads_id'];
 			$oneAds = $arrAdsDetails[$adsID];
 			if(!$oneAds){
 				continue;
 			}
 			//合作方CPM广告放到广告数组前面
 			if($oneAds['source'] == "ad_partner" && $oneAds['deliver_type'] == 2){
 				array_unshift($arrAds, $oneAds);
 			}else{
 				$arrAds[] = $oneAds;
 			}
 		}

 		$arrAds = array_slice($arrAds, 0, $pageSize);
 		return $arrAds;
 	}
 }

if(!function_exists('zk_ads_jingcai_ad')){
	/**
	 * 查找精彩推荐的广告
	 * @param string $oReq
	 * @return mixed
	 */
	function zk_ads_jingcai_ad($oReq){
		$arrAdsArticles = array();
		
		$oReq['ads_group'] = 'article_jingcai';
		
		list($arrRecommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('article_jingcai',$oReq);
		$adnum = zk_ads_config('jingcaituijian_ad_num');
		
		//读取用户喜爱的分类,权重
		list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
		
		for ($i=0;$i<$adnum;$i++){
			$ads = $arrAdsDetails[$arrAdsScore[$i]['ads_id']];
			if(!empty($ads)){
				$arrAdsArticles[] = zk_ads_format_article_jingcai($ads,$oReq);
				//2015-08-12加上判断,修复之前几个版本曝光数重复统计了
				if($oReq['_version']<6.1){

				    //统计广告曝光
				    zk_ads_cache_ads_show_count_incr(strval($ads['_id']), 1);
				    //统计用户看过的广告
                    zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($ads['_id']));
				    //统计用户今天看过的广告
                    zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($ads['_id']).'_'.date('Y-m-d'),1,strtotime(date('Y-m-d',strtotime('+1 day')))-time());
				    //入动作统计队列
				    $arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
				    $arrQueue = array(
				    		'ads_group' => $ads['ads_group'],
				    		'ads_id' => strval($ads['_id']),
				    		'event_type' => 'show',
				    		'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
				    		'device_id' => strval($oReq['device_id']),
				    		'udid' => $oReq['_udid'],
				    		'dtime' => $oReq['now'],
				    		'province' => $oReq['_province'],
				    		'city' => $oReq['_city'],
				    		'ip' => $oReq['ip'],
				    		'user_tag' => '',
				    		'user_category' => implode(" ", $arrUserFavTypeIds),
				    		'block_pk' => intval($oReq['app_id']),
				    		'prize_weight' => $ads['prize_weight'],
				    		'ad_info'=>$ads,
				    		'deliver_type' => strval($ads['deliver_type']),
				    		'cp_app_id'=>$oReq['cp_app_id'],
                            'creative_id'=>$oReq['creative_id'],
				    		//新增统计参数
				    		'new_app_id' => trim((string)$oReq['new_app_id']),
				    		'app_version' => trim((string)$oReq['_version']),
				    		'category_first' => strval($ads['category_first']),
				    		'category_second' => strval($ads['category_second']),
				    		'category_third' => strval($ads['category_third']),
				    );
				    	
				    if(!empty($ads['ad_group_id'])){
				    	$arrQueue['ad_group_id'] = strval($ads['ad_group_id']);
				    }
				    	
				    zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_STAT, $arrQueue);
				    	
				    //统计广告组曝光
				    if(!empty($ads['ad_group_id'])){
				    	zk_ads_cache_ads_show_count_incr($ads['ad_group_id']);
				    	zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], $ads['ad_group_id']);
				    }
			    }
			}
		}
		
		if(ZK_ADS_IS_SHOW_ADS == 0){
			return array();
		}
		
		return $arrAdsArticles;
	}
}

if(!function_exists('zk_ads_wap_jingcai_ad')){
	/**
	 * wap版查找精彩推荐的广告
	 * @param string $oReq
	 * @return mixed
	 */
	function zk_ads_wap_jingcai_ad($oReq){
		$arrAdsArticles = array();

		$oReq['ads_group'] = 'wap_jingcai';

		list($arrRecommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('wap_jingcai',$oReq);

		$adnum = 1;//zk_ads_config('jingcaituijian_ad_num');

		//读取用户喜爱的分类,权重
		list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);

		for ($i=0;$i<$adnum;$i++){
			$ads = $arrAdsDetails[$arrAdsScore[$i]['ads_id']];
			
			//wap精彩推荐只支持type2
// 			if($ads['ads_type'] != 2){
// 				continue;
// 			}
			
			if(!empty($ads)){
				$arrAdsArticles[] = zk_ads_format_wap_jingcai($ads,$oReq);
                
				//统计广告曝光
				zk_ads_cache_ads_show_count_incr(strval($ads['_id']), 1);
				//统计用户看过的广告
                zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($ads['_id']));
				//统计用户今天看过的广告
                zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], strval($ads['_id']).'_'.date('Y-m-d'),1,strtotime(date('Y-m-d',strtotime('+1 day')))-time());
             
				//入动作统计队列
				$arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
				$arrQueue = array(
						'ads_group' => $ads['ads_group'],
						'ads_id' => strval($ads['_id']),
						'event_type' => 'show',
						'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
						'device_id' => strval($oReq['device_id']),
						'udid' => $oReq['_udid'],
						'dtime' => $oReq['now'],
						'province' => $oReq['_province'],
						'city' => $oReq['_city'],
						'ip' => $oReq['ip'],
						'user_tag' => '',
						'user_category' => implode(" ", $arrUserFavTypeIds),
						'block_pk' => intval($oReq['app_id']),
						'prize_weight' => $ads['prize_weight'],
						'ad_info'=>$ads,
						'deliver_type' => strval($ads['deliver_type']),
						'cp_app_id'=>$oReq['cp_app_id'],
                        'creative_id'=>$oReq['creative_id'],
						//新增统计参数
						'new_app_id' => trim((string)$oReq['new_app_id']),
						'app_version' => trim((string)$oReq['_version']),
						'category_first' => strval($ads['category_first']),
						'category_second' => strval($ads['category_second']),
						'category_third' => strval($ads['category_third']),
				);
					
				if(!empty($ads['ad_group_id'])){
					$arrQueue['ad_group_id'] = strval($ads['ad_group_id']);
				}
					
				zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_STAT, $arrQueue);
					
				//统计广告组点击
				if(!empty($ads['ad_group_id'])){
					zk_ads_cache_ads_show_count_incr($ads['ad_group_id']);
					zk_ads_cache_user_ads_show_count_incr($oReq['_udid'], $ads['ad_group_id']);
				}
				 
			}
			 
		}

		if(ZK_ADS_IS_SHOW_ADS == 0){
			return array();
		}

		return $arrAdsArticles;
	}
}
		
if(!function_exists('zk_ads_recommend')){
	/**
	 * 推荐分 = (原始分10000 + 中喜爱分类*N + 中标签*N) * 点击率 * 广告价值 * N - 用户已展示次数*N - 用户已点击次数*N
	 * ----注意:新增的CPM积分方式,CMP的原始分为1000000----Eddie---20150702
	 * @param unknown $oReq
	 * @param unknown $arrAdsDetails
	 * @param unknown $arrRecommendConfig
	 * @param unknown $arrUserFavTypeIds
	 * @param unknown $arrUserTags
	 * @return list
	 */
	function zk_ads_recommend($oReq, $arrAdsDetails, $config, $arrUserFavTypeIds, $arrUserFavTypeWeight, $arrUserTags, $arrUserTagWeight, $arrAdsHitRates){
		
		$arrRecommendAdsID = null;
		
		$arrRecommendLog = array();
		
		$arrAdsScore = array();
		$outward_channels = zk_ads_config('outward_channels');  //外部导量渠道

		if(empty($arrAdsDetails)){
			return array(null, $arrAdsScore);
		}

		//用户最近看过哪些广告主的广告
		$arrUserViewedAdvs = array();
	  	if($oReq['device_id'] == 'default'){
			$arrUserViewedAdvs = zk_ads_cache_get_user_latest_viewed_advertisers($oReq['_udid']);
			zk_ads_add_log($arrUserViewedAdvs, 'USER_VIEWED_ADVERTISERS');
		}
		$advertiserCount = count($arrUserViewedAdvs); 

		$strictCategoryAd=array();
		$filterLocationAd=array();
		$userDeviceType=strtolower($oReq['_appid']);
        $perFav=$config['perFav']/10000;
        $perTag=$config['perTag']/10000;
        $today=date('Y-m-d');

		foreach ($arrAdsDetails as $sAdsID => $oneAdsDef){
			
			$campaignId = strval($oneAdsDef['ad_group_id']);
			//中喜爱分类
			$hitFavs = array_values(array_intersect($oneAdsDef['favour_category'], $arrUserFavTypeIds));
			//中标签
			$hitTags = array_values(array_intersect($oneAdsDef['tags'], $arrUserTags));
			//中频道
			$hitChannels = in_array($oReq['app_id'], $oneAdsDef['channel'])?0.2:0;
			//中喜爱分类的权重和
			$hitFavsWeight = 0;
			if(!empty($oneAdsDef['favour_category_with_weight'])){
				foreach ($oneAdsDef['favour_category_with_weight'] as $cateId => $weight) {
					if($arrUserFavTypeWeight[$cateId]){
						$hitFavsWeight += $arrUserFavTypeWeight[$cateId] + $weight;
					}
				}
			}else{
				foreach ($hitFavs as $oneFav){
					$hitFavsWeight += $arrUserFavTypeWeight[$oneFav];
				}
			}
			
			//中标签的权重和
			$hitTagsWeight = 0;
			foreach ($hitTags as $oneTag){
				$hitTagsWeight += $arrUserTagWeight[$oneTag];
			}
			
			//点击率
			$hitRate = $arrAdsHitRates[$sAdsID];

			//用户看过的广告，基础分要降低，得分计算公式有所区别
			if(!empty($arrUserViewedAdvs) && in_array($oneAdsDef['aid'], $arrUserViewedAdvs)){
				$index = array_search($oneAdsDef['aid'], $arrUserViewedAdvs);  //该广告主在第几位
				//越早看过的广告，基础分越高，可能优先对用户展示
				$baseScore = pow(2, $advertiserCount-$index-1) * 100;
	            $perShowWeight = 10;
	            $perClickWeight = 20;

	            $adPrice = ($oneAdsDef['deliver_type']==1) ? $oneAdsDef['prize_weight'] : $oneAdsDef['prize_weight']/10;
	            $adsScore = $baseScore * (1 + $hitRate * $adPrice * 100);

	            $formula = "{$baseScore} * ( 1 + {$hitRate} * {$adPrice} * 100) = {$adsScore}";
			}else{
				//cpm广告
				if($oneAdsDef['deliver_type']==2){
	              	$baseScore = $config['baseScoreCPM'];
	              	$perShowWeight = $config['perShowWeightCPM'];
	              	$perClickWeight = $config['perClickWeightCPM'];
				}else{
					if($oReq['ads_group'] == 'article_bottom_banner' && $oneAdsDef['ads_type'] == 1){
						//文章底部广告位置，增加大图类型广告的基础分，使大图广告优先展示
						$baseScore = $config['baseScoreCPC1'];
					}else{
						$baseScore = $config['baseScore'];
					}
					$perShowWeight = $config['perShowWeightCPC'];
	              	$perClickWeight = $config['perClickWeightCPC'];
				}
				if(!empty($oneAdsDef['location'])){ //设置了地域定向的广告，基础分增加1倍
					$baseScore = $baseScore * 2;
				}

				$showScore = max($oneAdsDef['userShows'], 1) *0.3 + $oneAdsDef['userShowsToday'];
				$priority = floatval($oneAdsDef['priority'])/10;

				$adsScore = $baseScore*(1 + $hitFavsWeight * $perFav + $hitTagsWeight * $perTag + $hitChannels)
							* $hitRate * $oneAdsDef['prize_weight'] * 100 * ($priority + 1)
							- intval($showScore) * $perShowWeight 
							- intval($oneAdsDef['userClicks']) * $perClickWeight;

				$formula = "{$baseScore} * ( 1 + ".$hitFavsWeight." * {$perFav} + ".$hitTagsWeight." * {$perTag} + {$hitChannels}) * {$hitRate} * {$oneAdsDef['prize_weight']} * 100 * ({$priority} + 1) - (".max($oneAdsDef['userShows'], 1) ."*0.3 +".(int)$oneAdsDef['userShowsToday'].") * {$perShowWeight} - ".intval($oneAdsDef['userClicks'])." * {$perClickWeight} = ".$adsScore;
			}

			
			$arrRecommendLog[$sAdsID] = array(
					'hitFavs' => $hitFavs,
					'hitTags' => $hitTags,
					'hitChannel'=>in_array($oReq['app_id'],$oneAdsDef['channel'])?$oReq['app_id']:'',
					'formula' => $formula,
					'score' => $adsScore,
					'aid' => $oneAdsDef['aid'],
					'ad_group_id'=>$oneAdsDef['ad_group_id']
			);

            //加上广告的广告组id用来算分后排重
            $arrAdInfo=array('ads_id' => $sAdsID, 'score' => $adsScore, 'deliver_type'=> $oneAdsDef['deliver_type'], 'aid' => $oneAdsDef['aid']);
            if(!empty($oneAdsDef['packageid'])){
                $arrAdInfo['packageid'] = $oneAdsDef['packageid'];
            }
			array_push($arrAdsScore, $arrAdInfo);
		}
 
		zk_ads_add_log($arrRecommendLog, 'ADS_RECOMMEND_LOG');
		//根据得分高低对广告排序
		usort($arrAdsScore, "zk_ads_score_sort");

        /**
         * 开始排重去掉同一广告组的广告（导量渠道返回的广告不需要排重）
         */
        $finalArrAdsScore=array();//排除掉相同广告组packageid的广告
        $finalPackageids=array();//所有广告组id
        $finalNotinPackageids=array();//排重掉的广告id
        foreach($arrAdsScore as $oneAdsScore) {
            //如果有广告组id
            if( !empty($oneAdsScore['packageid']) && !in_array($oReq['device_id'], $outward_channels) ) {
                //已经有同组分高的广告，直接跳过
                if(in_array($oneAdsScore['packageid'], $finalPackageids)) {
                    $finalNotinPackageids[]=$oneAdsScore['ads_id'];
                }else{
                    //如果没有同组广告，该广告可用
                    $finalArrAdsScore[] = $oneAdsScore;
                    $finalPackageids[] = $oneAdsScore['packageid'];
                }
            }else{
                //没有广告组id的都是可用的
                $finalArrAdsScore[]=$oneAdsScore;
            }
        }
        zk_ads_add_log($finalPackageids, 'ADS_FINAL_PACKAGEIDS');
        zk_ads_add_log($finalNotinPackageids, 'FILTER_ADS_BY_SAME_PACKAGE');

        $arrAdsScore=$finalArrAdsScore;
	
		if(count($arrAdsScore) > 0){
		 	$arrRecommendAdsID = $arrAdsScore[0]['ads_id'];
		// 	//CPM广告累加扣费缓存
		// 	cpmAdvertiserCostIncr($arrAdsDetails[$arrRecommendAdsID]);
		}
//        zk_ads_add_log($arrRecommendAdsID, 'AFTER_RECOMMEND_ADS_ID');
		return array($arrRecommendAdsID, $arrAdsScore);
		
	}
	
}


if(!function_exists('zk_ads_score_sort')){

	function zk_ads_score_sort($a, $b) {
		if ($a ["score"] == $b ["score"])
			return 0;
		return ($a ["score"] > $b ["score"]) ? - 1 : 1;
	}

}

if(!function_exists('zk_ads_get_cookie_udid')){
	
	function zk_ads_get_cookie_udid(){
		
		$_udid = $_COOKIE['zk_ads_udid'];
		
		if(!empty($_udid) &&(!strpos($_udid, "192.168.88")||!strpos($_udid, "192-168-88"))){
			return $_udid;
		}else{
			$ip = zk_ads_get_user_ip();
			
			$_udid = "zk_ads_".str_replace(".", "-", trim($ip))."_".substr(md5($_SERVER['HTTP_USER_AGENT']), 0, 8);
			
			setcookie("zk_ads_udid", $_udid, time()+86400*365);
			
			return $_udid;
		}
		
	}
	
}

if(!function_exists('zk_ads_get_user_ip')){

	function zk_ads_get_user_ip($type = 0){

		$type 		= $type ? 1 : 0;
		static $ip 	= NULL;
		if ($ip !== NULL) return $ip[$type];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr 	= explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos 	= array_search('unknown',$arr);
			if(false !== $pos) unset($arr[$pos]);
			$ip 	= trim($arr[0]);
		} else if (isset($_SERVER['HTTP_IP_FORWARDED_FOR'])) {
			$arr 	= explode(',', $_SERVER['HTTP_IP_FORWARDED_FOR']);
			$pos 	= array_search('unknown',$arr);
			if(false !== $pos) unset($arr[$pos]);
			$ip 	= trim($arr[0]);
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip 	= $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long 	= ip2long($ip);
		$ip 	= $long ? array($ip, $long) : array('0.0.0.0', 0);
		return $ip[$type];

	}

}

if(!function_exists('zk_ads_get_request_params')){
	
	function zk_ads_get_simple_request_params($need_udid = FALSE, $need_location = FALSE){
		$oReq = array();
		
		$_appid_arr = array(
				'ipad' => 'ipad',
				'iphone' => 'iphone',
				'androidphone' => 'androidphone',
				'androidpad' => 'androidpad',
				'windowphone' => 'wphone',
				'windows8' => 'win8',
				'web' => 'web'
		);
		list($_appid,$is_m) = zk_ads_check_device_by_ua();
		$_appid = $_appid_arr[$_appid];
		if(!$_appid){
			$_appid = 'web';
		}
		
		// 当前时间
		$oReq['now']						= time();
		// 当前日期
		$oReq['nowDate']					= date("Y-m-d");
		// 平台
		$oReq['_appid']						= isset($_REQUEST['_appid'])		? strtolower(trim($_REQUEST['_appid'])) : $_appid;
		// 渠道
		$oReq['device_id']					= zk_ads_check_device_id_by_ua();
		//测试写死iphone
// 		$oReq['_appid'] = 'iphone';
		// 广告展示类型
		$oReq['ads_type']					= isset($_REQUEST['ads_type'])		? intval($_REQUEST['ads_type'])			: NULL;
		// uid
		$oReq['_uid']						= isset($_REQUEST['_uid']) 			? intval($_REQUEST['_uid'])				: NULL;
		// idfa
        $oReq['_idfa']                        = !empty($_REQUEST['_idfa'])        ? trim((string)$_REQUEST['_idfa'])         : NULL;
        // mac
        $oReq['_mac']                        = !empty($_REQUEST['_mac'])            ? trim((string)$_REQUEST['_mac'])        : NULL;

        // imei
        if(!empty($_REQUEST['_imei'])) {
        	$oReq['_imei']                   =  trim((string)$_REQUEST['_imei']);
        }elseif ( !empty($_REQUEST['udid_auth']) && trim($_REQUEST['udid_auth']) == 'imei' ) {

        	if ( !empty($_REQUEST['_udid']) && strlen($_REQUEST['_udid']) == 15 ) {
        		$oReq['_imei']				=	trim((string)$_REQUEST['_udid']);
        	}

        }
        

		// UDID
		if($need_udid){
			$oReq['_udid'] 					= !empty($_REQUEST['_udid'])			? trim((string)$_REQUEST['_udid']) 		: zk_ads_get_cookie_udid();
		}
		$oReq['user_agent']					= trim($_SERVER['HTTP_USER_AGENT']);
		// app_id
		$oReq['app_id'] 					= isset($_REQUEST['app_id'])		? trim((string)$_REQUEST['app_id']) 	: NULL;
		// 兴趣分类id
		$oReq['new_app_id'] 				= isset($_REQUEST['new_app_id'])		? trim((string)$_REQUEST['new_app_id']) 	: NULL;
		// 文章pk
		$oReq['pk'] 						= isset($_REQUEST['pk'])			? trim((string)$_REQUEST['pk']) 		: NULL;
		// ip
		$oReq['ip']							= !empty($_REQUEST['client_ip'])  ? trim($_REQUEST['client_ip']) : zk_simple_get_user_ip();
		// 作者名
		$oReq['author'] 					= isset($_REQUEST['author'])		? trim((string)$_REQUEST['author'])		: NULL;
		// 文章域名
		$oReq['url_host'] 					= isset($_REQUEST['url_host'])		? trim((string)$_REQUEST['url_host'])	: NULL;
		// 渠道号
		$oReq['_dev'] 						= isset($_REQUEST['_dev'])			? trim((string)$_REQUEST['_dev'])		: NULL;
		// wap版渠道号
		$oReq['f'] 							= isset($_REQUEST['f'])				? trim((string)$_REQUEST['f'])			: NULL;
		// 接口版本号
		$oReq['_version']					= isset($_REQUEST['_version'])		? $_REQUEST['_version']					: NULL;
		// debug模式
		$oReq['debug']						= $_REQUEST['debug'] > 0			? TRUE									: FALSE;
		// 显示调试信息
		$oReq['show_log']					= $_REQUEST['show_log'] > 0			? TRUE									: FALSE;
		// 测试标签
		$oReq['_user_tag'] 					= isset($_REQUEST['_user_tag'])		? trim((string)$_REQUEST['_user_tag'])	: NULL;
		// 测试分类
		$oReq['_user_category'] 			= isset($_REQUEST['_user_category'])	? trim((string)$_REQUEST['_user_category']) 	: NULL;
		//跳转地址
		$oReq['ad_url'] 					= !empty($_REQUEST['ad_url'])		? trim((string)$_REQUEST['ad_url'])	: '';
		//创意ID
		$oReq['creative_id'] 				= empty($_REQUEST['creative_id'])   ? trim((string)$_REQUEST['creative_id'])	: '';
		$oReq['_net'] 				        = isset($_REQUEST['_net'])?$_REQUEST['_net']=='wifi'?'wifi':'mobile':'';
		$oReq['phone_brand'] 				= !empty($_REQUEST['phone_brand'])		? trim((string)$_REQUEST['phone_brand'])	: '';
		if($oReq['_appid']=='iphone'){
		  $oReq['phone_brand']= 'APPLE';

		}
		$oReq['_os_name']                   = !empty($_REQUEST['_os_name'])		? trim((string)$_REQUEST['_os_name'])	: '';
		$oReq['carrier'] 				    = !empty($_REQUEST['carrier'])		? trim((string)$_REQUEST['carrier'])	: '';
		$oReq['sex'] 				    = !empty($_REQUEST['sex'])		? trim((string)$_REQUEST['sex'])	: '';

        $oReq['client_sdk_version']     = !empty($_REQUEST['client_sdk_version']) ? trim((string)$_REQUEST['client_sdk_version']) : '';

		$oReq['author']   = !empty($_REQUEST['author'])   ? trim((string)$_REQUEST['author'])	: '';
		$oReq['url_host'] = !empty($_REQUEST['url_host'])   ? trim((string)$_REQUEST['url_host'])	: '';

		//cms_app_id
		$oReq['cms_app_id'] 	         = isset($_REQUEST['cms_app_id'])		? (int)$_REQUEST['cms_app_id']	: NULL;
		
		if($need_location){
			zk_ads_check_location($oReq);
		}

		//ad_udid
		$oReq['ad_udid'] = isset($_REQUEST['ad_udid']) ? trim((string)$_REQUEST['ad_udid']) : zk_ads_get_ad_udid();

		// 合作方交易id
		$oReq['deal_id'] = isset($_REQUEST['deal_id']) ? trim((string)$_REQUEST['deal_id']) : '';

		//获取媒体cp_app_id
		if(!empty($oReq['cms_app_id'])){
		    $cp_app_id = $oReq['cms_app_id'];
		}elseif(!empty($oReq['app_id']) || !empty($oReq['author']) || !empty($oReq['url_host'])){
			
			if(!function_exists('get_cp_define')){
				require_once (dirname ( __FILE__ ) ."/cp_define_helper.php");
			}
			if(empty($GLOBALS['arrAuthorDefine']) || empty($GLOBALS['arrUrlHostDefine'])){
				list($GLOBALS['arrAuthorDefine'],$GLOBALS['arrUrlHostDefine']) = get_cp_define_options();
			}
			
			//优先用作者名和域名判断
			$cp_app_id = get_cp_define($oReq['author'], $oReq['url_host'], $GLOBALS['arrAuthorDefine'], $GLOBALS['arrUrlHostDefine']);
			
			//保底用app_id判断
			if(empty($cp_app_id) && !empty($oReq['app_id'])){
				if(empty($GLOBALS['arrCpApp'])){
					$GLOBALS['arrCpApp'] = get_cp_apps();
				}
				$cp_app_id = strval($GLOBALS['arrCpApp'][$oReq['app_id']]['app_id']);
			}
			
		}

		//本地媒体推到其他频道的原创文章
		if(empty($cp_app_id) && !empty($_REQUEST['rgcms_app_id']) && !empty($_REQUEST['is_original'])) {
			$oReq['rgcms_app_id'] = $_REQUEST['rgcms_app_id'];
			$oReq['is_original'] = $_REQUEST['is_original'];
			$cp_app_id = $_REQUEST['rgcms_app_id'];
		}

		/*
         * 本地媒体频道，只有从本频道内转发到微信的算流量(保留cp_app_id)
         */

		//第三方渠道的流量
		if( $oReq['device_id'] != 'default' ){
			//获取本地媒体id配置列表
			try{
				$rgcmsIdArrs =  require(dirname(__FILE__)."/../config/rgcms_app_ids.php");
			}catch (Exception $e) {

			}

			//如果是本地媒体
			if( !empty($rgcmsIdArrs) && is_array($rgcmsIdArrs) && in_array($oReq['app_id'], $rgcmsIdArrs) ) {
				//只保留微信的，其他第三方全都去掉cp_app_id
				if( $oReq['device_id'] != 'weixin') {
					$cp_app_id = null;
				}
			}
		}
		
		$oReq['cp_app_id'] = strval($cp_app_id);

		//屏幕尺寸
		$oReq['_bsize'] = $_REQUEST['_bsize'] ? strval($_REQUEST['_bsize']) : null;

		// os
        $oReq['os'] = $_REQUEST['_os'] ? strval($_REQUEST['_os']) : null;

		return $oReq;
	}
	
}



if(!function_exists('zk_ads_get_ad_udid')) {
	function zk_ads_get_ad_udid() {

		$ad_udid = '';

		if (in_array($_REQUEST['_appid'] , array('iphone','ipad') ) && !empty($_REQUEST['_idfa'])) {
			//iphone的保存idfa
			$ad_udid = trim((string)$_REQUEST['_idfa']);
		} elseif ( in_array($_REQUEST['_appid'] , array('androidphone','androidpad')) && !empty($_REQUEST['_udid']) && strlen($_REQUEST['_udid']) == 15 ) {
			//安卓机udid长度15位的是imei
			$ad_udid = trim((string)$_REQUEST['_udid']);
		}

		return $ad_udid;
	}
}


/**
 * 获取deal_id from unidesk
 */
if(!function_exists('zk_ads_get_unidesk_deal_id')) {
	function zk_ads_get_unidesk_deal_id() {
	    return ZK_ADS_UNIDESK_DEAL_ID;
	}
}

/**
 * 获取合作方deal_id
 * @parm string $partnerId
 */
if(!function_exists('zk_ads_get_partner_deal_id')) {
    function zk_ads_get_partner_deal_id($partnerId) {
        if ($partnerId == 'unidesk') {
            return zk_ads_get_unidesk_deal_id();
        } else {
            return $partnerId.'_1';
        }
    }
}

if(!function_exists('zk_ads_check_location')){
	function zk_ads_check_location(&$oReq){
            $oReq['_lat'] = isset($_REQUEST['_lat']) ? trim($_REQUEST['_lat']) : 0;
            $oReq['_lon'] = isset($_REQUEST['_lng']) ? trim($_REQUEST['_lng']) : 0;
            $convert = zk_ads_convert_lbs($oReq['_appid'], $oReq['_lat'], $oReq['_lon']);
            $oReq['_lat'] = $convert[0];
            $oReq['_lon'] = $convert[1];

			if(!empty($_REQUEST['city_code']) && !empty($_REQUEST['province_code'])){
				$oReq['_province'] = $_REQUEST['province_code'];
            	$oReq['_city'] = $_REQUEST['city_code'];
            	return;
			}
            // lbs城市
			$oReq['_lbs_city']= isset($_REQUEST['_lbs_city'])? trim((string)$_REQUEST['_lbs_city']): '';
			// lbs省份
			$oReq['_lbs_province']= isset($_REQUEST['_lbs_province'])? trim((string)$_REQUEST['_lbs_province'])	: '';
			// 城市
			$oReq['_city']	= isset($_REQUEST['_city'])? trim((string)$_REQUEST['_city']): '';
			// 省份
			$oReq['_province']= isset($_REQUEST['_province'])? trim((string)$_REQUEST['_province'])	: '';
            
            load_class('zk_ip_location');
            $ip_location = Zk_Ip_Location::getInstance();
            if(!empty($oReq['_lbs_city']) || !empty($oReq['_city']) ){
	           	load_class('zk_lbs_location');
	           	$lbs_location = Zk_Lbs_Location::getInstance();
            }
            
            list($city_code,$city_name,$province_code,$province_name) = zk_ads_get_user_location($lbs_location, $ip_location, $oReq['ip'], $oReq['_city'], $oReq['_lbs_city']);
            $oReq['_province']=$province_code;
            $oReq['_city']=$city_code;
	}
}


/**
 * 坐标系转换(ios: google=>baidu, android: famat)
 */
if (!function_exists('zk_ads_convert_lbs')) {
    function zk_ads_convert_lbs($_appid, $lat, $lng) {
        if($lat == 0 && $lng == 0 ){
            return array(0, 0);
        }

        $lat = floatval($lat);
        $lng = floatval($lng);

        if(abs($lat) < 0.00000001) $lat = 0;
        if(abs($lng) < 0.00000001) $lng = 0;

        if(!class_exists('GpsPositionTransform')){
            require_once (dirname ( __FILE__ ) . '/../classes/GpsPositionTransform.php');
        }

        if ($_appid == 'iphone' || $_appid == 'iphone_pro') {
            $lbs = GpsPositionTransform::gcj02_To_Bd09($lat, $lng);
            return array(floatval($lbs['lat']), floatval($lbs['lon']));
        } else {
            return array(floatval($lat), floatval($lng));
        }
    }
}


/**
 * 匹配出用户地理位置
 */
if(!function_exists('zk_ads_get_user_location')){
	function zk_ads_get_user_location($lbs_location = null, $ip_location = null, $ip = null, $_city = null, $_lbs_city = null){
	
		$cur_city = !empty($_lbs_city) ? $_lbs_city : $_city;
		//用LBS地址或者客户端设置的城市 获取城市和省份
		list($city_code,$city_name,$province_code,$province_name) = zk_ads_get_lbs_location($lbs_location, $cur_city);
		
		//用ip获取获取城市和省份
		if(empty($city_code) && !empty($ip) && is_object($ip_location)){
			if(!empty($ip)){
				$location = $ip_location->getLocation($ip);
			}
			if(!empty($location['city_code'])){
				$city_code = strtolower($location['city_code']);
				$city_name = strtolower($location['city_name']);
			}
			if(!empty($location['province_code']) && is_object($lbs_location)){
				$province_info = $lbs_location->getProvinceInfo($location['province_name']);
				$province_code = strval($location['province_code']);
				$province_name = $province_info['province_name'];
			}elseif(!empty($location['province_code'])){
				$province_code = strval($location['province_code']);
				$province_name = $location['province_name'];
			}else{
				$province_code = null;
				$province_name = null;
			}
		}
		
		return array($city_code,$city_name,$province_code,$province_name);
	}
}

/**
 * 根据LBS地址库获取城市和省份
 */
if(!function_exists('zk_ads_get_lbs_location')){
	function zk_ads_get_lbs_location($lbs_location = null, $cur_city = ''){
		$city_code = null;
		$city_name = null;
		$province_code = null;
		$province_name = null;
	
		if(strpos($cur_city,'市')!==false){
			$cur_city = str_replace('市','',$cur_city);
		}
		//匹配城市
		if(!empty($cur_city) && is_object($lbs_location)){
			$city_info = $lbs_location->getCityInfo(strtolower($cur_city));	//英文的情况
			if(!empty($city_info['city_name'])){
				$city_code = $city_info['city_char'];
				$city_name = $city_info['city_name'];
			}
			if(!empty($city_info['parent'])){
				$province_code = $lbs_location->getProvinceCode($city_info['parent']);
				$province_name = $city_info['parent'];
			}
		}
		
		return array($city_code,$city_name,$province_code,$province_name);
	}
}

if(!function_exists('zk_ads_check_device_by_ua')){

	function zk_ads_check_device_by_ua(){
		//判断是否是手机浏览器
		$agent = $_SERVER['HTTP_USER_AGENT'];
		
		$is_m = false;
		
		if(stripos($agent,"NetFront") || stripos($agent,"iPhone") || stripos($agent,"MIDP-2.0") || stripos($agent,"Opera Mini")
			 || stripos($agent,"UCWEB") || stripos($agent,"Android") || stripos($agent,"Windows CE") || stripos($agent,"SymbianOS")
			 || stripos($agent,"MQQBrowser") || stripos($agent,"weibo") || stripos($agent,"QQ") || stripos($agent,"MicroMessenger")){
		
			$is_m = true;
		}
		
		if(stristr($agent,'iphone') || stristr($agent,'ipod')){
			$is_m = true;
			$_appid = 'iphone';
		}
		elseif (stristr($agent,'iPad')){
			$is_m = true;
			$_appid = 'ipad';
		}
		
		elseif (stristr($agent,'android')){
			$is_m = true;
			$_appid = 'androidphone';
		}
		
		elseif (stristr($agent,'Windows Phone')){
			$is_m = true;
			$_appid = 'windowphone';
		}
		
		elseif(stristr($agent,'windows') && (stristr($agent,'webview') || stristr($agent,'touch') )  ){
			$is_m = true;
			$_appid = 'windows8';
		}
		
		
		return array($_appid, $is_m);
	}

}

if(!function_exists('zk_ads_check_device_id_by_ua')){
	/*
	 * 判断渠道
	 */
	function zk_ads_check_device_id_by_ua(){
		
		$f = $_REQUEST['f'];
		
		$ua = $_SERVER['HTTP_USER_AGENT'];
		
		$device_id = '';
	
		if(stristr($ua,'weibo')){
			$device_id = 'weibo';
		}
		elseif (stristr($ua,'MicroMessenger')){
			$device_id = 'weixin';
		}
		elseif (strstr($ua,'QQBrowser')){
			$device_id = 'QQBrowser';
		}
		elseif (strstr($ua,'QQ')){
			$device_id = 'qq';
		}
		
		if(empty($device_id) || ($device_id=="QQBrowser" && !empty($f))){
			$device_id = $f;
		}
		
		if(empty($device_id) || $device_id == 'Normal'){
			$device_id = 'default';
		}
		
		return $device_id;
	}

}
	
if(!function_exists('zk_ads_check_ad_permission')){
	
	function zk_ads_check_ad_permission($oReq){
		
		//第三方广告输出
		if($oReq['mycheering_ad']){
			return true;
		}
		//为了应对苹果审核，iphone 8.39版本的国外IP暂时不出DSP广告
		if($oReq['_appid'] == "iphone" && $oReq['_version'] == 8.39 && empty($oReq['_city'])){
			return false;
		}

		$arrAdsDisableBlocks = zk_ads_config('disable_ad_block');
		$arrAdsEnableBlockPageBlocks = zk_ads_config('enable_blockpage_ad_block');
		$arrAdsZijianBlocks = zk_ads_config('zijian_block');
		$disabledBlocks = $arrAdsDisableBlocks[$oReq['ads_group']];
		$disabledChannels = zk_ads_config('disabled_channels');
		//某些第三方渠道不出广告
		if(in_array($oReq['device_id'], $disabledChannels)){
			return false;
		}

		//判断文章底部是否能出广告
		if(in_array($oReq['ads_group'], array('article_bottom_banner', 'wap_bottom_banner'))){				
			if(in_array($oReq['app_id'], $arrAdsZijianBlocks)){	//自建频道
				if(is_array($disabledBlocks) && in_array($oReq['cp_app_id'], $disabledBlocks)){
					return false;
				}
			}
			
			if(is_array($disabledBlocks) && in_array($oReq['app_id'], $disabledBlocks)){
				return false;
			}
		}
		elseif(in_array($oReq['ads_group'], array('block_page')) && !in_array($oReq['app_id'], $arrAdsZijianBlocks) && !in_array($oReq['app_id'], $arrAdsEnableBlockPageBlocks)){
			//判断频道列表是否能出广告
			return false;
		}
		elseif(is_array($disabledBlocks) && in_array($oReq['app_id'], $disabledBlocks)){
			//判断其他位置是否能出广告
			return false;
		}
		
		return true;
	}
	
}

if(!function_exists('zk_ads_preload_ads_def')){

	function zk_ads_preload_ads_def($nNowTimeStep = 0){
		$nNowTime = time();
		
		zk_ads_add_log("time: ".microtime(true), 'preload_begin');

		$tongchengAdvs = zk_ads_config('58_tongcheng_advertisers');  //58同城相关广告主
		//redis
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		
		if(!$isRedisConnected){
			zk_ads_add_log("connect redis error: ".microtime(true), 'ERROR');
			return FALSE;
		}
		//使用Redis pipeline，减少交互次数，提升效率
		$oRedis = $oRedis->multi(Redis::PIPELINE);
		
		//停用过期的广告
		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_DEF);
		$arrWheres = array(
				'stat' => 1,
				'end_time' => array('$lt' => $nNowTime),
		);
		$updateArr = array();
		$updateArr['$set']['stat'] = 0;
		$db->where($arrWheres);
		$result = $db->update_all( ZK_MONGO_TB_ZK_ADS_DEF, $updateArr );
		
		//获取有余额的广告资源
		$arrWheres = array(
				'start_time' => array('$lt' => $nNowTime),
				'end_time' => array('$gt' => $nNowTime),
				'stat' => 1,
				'no_balance' => array('$ne' => 1),  //有余额
		);
		$arrAdsDef = zk_ads_get_def_data($arrWheres, array());
		
		if(empty($arrAdsDef)){
			zk_ads_add_log('Can not get ads info', 'ERROR2');
		}
		zk_ads_add_log(count($arrAdsDef), 'ads_num');

		//获取广告组定义
		$arrAdGroupDef = array();
		// $arrWheres = array(
		// 		'start_time' => array('$lt' => $nNowTime),
		// 		'end_time' => array('$gt' => $nNowTime),
		// 		'stat' => 1
		// );
		
		//----------修改读取广告组的条件  Eddie---------
		$arrWheres = array(
				//'start_time' => array('$lt' => $nNowTime),
				//'end_time' => array('$gt' => $nNowTime),
				'status' => 2
		);
		//----------修改读取广告组的条件  Eddie---------
		$adGroupIds=array();
		$resAdGroupDef = zk_ads_get_ad_group_def_data($arrWheres);
		foreach ($resAdGroupDef as $oneAdGroup){
			if(!empty($oneAdGroup['_id'])){
				$oneAdGroup['_id'] = strval($oneAdGroup['_id']);
				$adGroupIds[]=$oneAdGroup['_id'];
				$arrAdGroupDef[$oneAdGroup['_id']] = $oneAdGroup;
			}
		}
		unset($resAdGroupDef);
		
		//获取喜爱分类, 刷新缓存
		$db_fav = db_mysql_conn(ZK_MYSQL_TB_MINI_APPS_OPTION,true);
		$db_fav ->select(array('category_id','category_name'));
		$db_fav ->where('stat',1);
		$db_fav ->where('category_id <',1000);
		$db_fav ->limit(100);
		$query = $db_fav ->get(ZK_MYSQL_TB_MINI_APPS_OPTION);
		if(0 < $query->num_rows()){
			$arrFavCtg = $query->result_array();
		}else{
			$arrFavCtg = array();
		}
		
		$arrFavCache = array();
		foreach ($arrFavCtg as $arrOneFav){
			if(!empty($arrOneFav['category_id']) && !empty($arrOneFav['category_name'])){
				$arrFavCache[$arrOneFav['category_id']] = $arrOneFav;
			}
		}
		
		
		$re = $oRedis->set(ZK_ADS_CACHE_ALL_FAV_CTG, json_encode($arrFavCache));
		$re = $oRedis->setTimeout(ZK_ADS_CACHE_ALL_FAV_CTG, 3600*12);
		
		//----------创意--------------
		$mongo = db_mongoDB_conn(ZK_MONGO_TB_DSP_CREATIVE);
		$conditions = array(
			'status'=>2,
			'campaignid' =>array('$in'=>$adGroupIds),
				 
		); 
		$mongo->where($conditions);
		$creatives = $mongo->get( ZK_MONGO_TB_DSP_CREATIVE );
		$creativesMap=array();
		$creativeIds=array(); 
		if($creatives){
			foreach ($creatives as $key => $value) {
			  $value['_id']=(string)$value['_id'];
			  $creativesMap[$value['campaignid']][$value['_id']]=$value;
              $creativeIds[]=$value['_id'];
			}
		}

		//获取广告计划被哪些渠道采用了
		$partnerIds = zk_ads_config('third_party_id');
		$campaignPartners = array();
		if($partnerIds){
			foreach ($partnerIds as $pid) {
				$campaigns = zk_get_campaign_ids_by_device($pid);
				foreach ($campaigns as $cid) {
					$campaignPartners[$cid][] = $pid;
				}
			}
		}

		/**
		 * 每条广告设置缓存
		 * 全部广告ID设置缓存
		 * 广告ID按喜好分类归总，设置缓存
		 * 广告ID按设备平台归总，设置缓存
		 * 广告ID按设备平台、广告分组、喜好分类归总，设置缓存
		 * 广告ID按设备平台、广告分组、地域归总，设置缓存
		 * 广告ID按设备平台、广告分组、标签归总，设置缓存
		*/
		$arrAdsAll = array();
		$arrAds58 = array();  //58同城的广告集合
		$adsIds = array();
		$arrAdsCollapseByDevice = array();
		$arrAdsCollapseByDeviceAndGroupAndFav = array();
		$arrAdsCollapseByDeviceAndGroupAndLocation = array();
		$arrAdsCollapseByDeviceAndGroupAndTag = array();
		$arrAdsCollapseByDeviceAndGroupAndChannel = array();
		$categories = zk_ads_get_categories();
		foreach ($arrAdsDef as $oneAds){
		
			$ads_id = strval($oneAds['_id']);
			$oneAds['_id'] = strval($oneAds['_id']);
			$campaignId = strval($oneAds['ad_group_id']);
			$adsIds[] = $ads_id;
			//删除状态不正常的广告缓存
			if($oneAds['stat']!=1){
               $oRedis->del(ZK_ADS_CACHE_SINGLE_ADS_DEF.$ads_id);
               continue;
			}
			//加入广告组定义
			if(!empty($oneAds['ad_group_id']) && is_array($arrAdGroupDef[$oneAds['ad_group_id']])){
				$oneAds['ad_group_def'] = array(
						'_id' => $arrAdGroupDef[$oneAds['ad_group_id']]['_id'],
						'target_clicks' => $arrAdGroupDef[$oneAds['ad_group_id']]['target_clicks'],
						'daily_target_clicks' => $arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_clicks'],
	                    'target_views' => $arrAdGroupDef[$oneAds['ad_group_id']]['target_views'],
						'daily_target_views' => $arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_views'],
				);  
				//cpc
				if($arrAdGroupDef[$oneAds['ad_group_id']]['target_clicks'] > 0){
					$oneAds['target_clicks'] = $arrAdGroupDef[$oneAds['ad_group_id']]['target_clicks'];
				}
				if($arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_clicks'] > 0){
					$oneAds['daily_target_clicks'] = $arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_clicks'];
				}

                //cpm
				//if($arrAdGroupDef[$oneAds['ad_group_id']]['target_views'] > 0){
					$oneAds['target_views'] = (int)$arrAdGroupDef[$oneAds['ad_group_id']]['target_views'];
				//}
				//if($arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_views'] > 0){
					$oneAds['daily_target_views'] = (int)$arrAdGroupDef[$oneAds['ad_group_id']]['daily_target_views'];
				//}
			}
			//给有勾选喜好分类的广告加上“新闻”分类
			//if(count($oneAds['favour_category']) > 0 && !in_array('15', $oneAds['favour_category'])){
				//$oneAds['favour_category'][] = '15';
			//}

			//加入全部ID数组
			if(!in_array($ads_id, $arrAdsAll)){
				array_push($arrAdsAll, $ads_id);
			}else{
				continue;
			}
			//----------创意--------------
	        if($creativesMap[$oneAds['ad_group_id']]){
	        	$oneAds['creatives']=$creativesMap[$oneAds['ad_group_id']];
	        }else{
	        	continue; //没有找到广告创意的不加载
	        }
	       
	        //----------创意--------------

	        if(in_array($oneAds['aid'], $tongchengAdvs)){
	        	array_push($arrAds58, $ads_id);  //58同城的广告集合
	        }

	        if($categories[$oneAds['category']]){
				$oneAds['tags'][] = $categories[$oneAds['category']]; //将广告所属小类加入广告标签
			}
			//广告被哪些渠道采用了
			if($campaignPartners[$campaignId]){
				$oneAds['for_third_party'] = $campaignPartners[$campaignId];
			}

			//设置单条广告缓存
			$re = $oRedis->set(ZK_ADS_CACHE_SINGLE_ADS_DEF.$ads_id, json_encode($oneAds));
			$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_ADS_DEF.$ads_id, 3300);
		
			//按喜好分类归总
			// 		foreach ($oneAds['favour_category'] as $oneFavID){
			// 			$arrFavCache[$oneFavID]['ads_ids'][] = $ads_id;
			// 		}
		
			//按平台归总
			// 		foreach ($oneAds['device_type'] as $oneDeviceID){
			// 			$arrAdsCollapseByDevice[$oneDeviceID][] = $ads_id;
			// 		}
			
			//测试广告
			if($oneAds['is_test'] == 1){
				
				//按平台、分组、喜好归总
				foreach ($oneAds['device_type'] as $oneDeviceID){
					$arrAdsCollapseByDeviceAndGroupAndFav[$oneDeviceID][$oneAds['ads_group']][99][] = $ads_id;
				}
				//按平台、分组、地域归总
				if(!empty($oneAds['location']) && count($oneAds['location']) > 0){
					foreach ($oneAds['device_type'] as $oneDeviceID){
						foreach ($oneAds['location'] as $oneLocation){
							$arrAdsCollapseByDeviceAndGroupAndLocation[$oneDeviceID][$oneAds['ads_group']][$oneLocation.'_test'][] = $ads_id;
						}
					}
				}
				//按平台、分组、频道归总
				if($oneAds['channel']){
				    foreach ($oneAds['device_type'] as $oneDeviceID){
				    	foreach ($oneAds['channel'] as $oneChannel){
				    		if(empty($oneAds['location'])){
				    			$arrAdsCollapseByDeviceAndGroupAndChannel[$oneDeviceID][$oneAds['ads_group']][862][] = $ads_id;
				    		}
				    	}
				    }
			    }
			}else{
			
				//按平台、分组、喜好归总
				foreach ($oneAds['device_type'] as $oneDeviceID){
					foreach ($oneAds['favour_category'] as $oneFavID){
						if(empty($oneAds['location'])){
							$arrAdsCollapseByDeviceAndGroupAndFav[$oneDeviceID][$oneAds['ads_group']][$oneFavID][] = $ads_id;
						}
					}
				}
			
				//按平台、分组、地域归总
				if(!empty($oneAds['location']) && count($oneAds['location']) > 0){
					foreach ($oneAds['device_type'] as $oneDeviceID){
						foreach ($oneAds['location'] as $oneLocation){
							$arrAdsCollapseByDeviceAndGroupAndLocation[$oneDeviceID][$oneAds['ads_group']][$oneLocation][] = $ads_id;
						}
					}
				}
			
				//按平台、分组、标签归总
				foreach ($oneAds['device_type'] as $oneDeviceID){
					foreach ($oneAds['tags'] as $oneTag){
						if(empty($oneAds['location'])){
							$arrAdsCollapseByDeviceAndGroupAndTag[$oneDeviceID][$oneAds['ads_group']][$oneTag][] = $ads_id;
						}
					}
				}

				//按平台、分组、频道归总
				if($oneAds['channel']){
				    foreach ($oneAds['device_type'] as $oneDeviceID){
				    	foreach ($oneAds['channel'] as $oneChannel){
				    		if(empty($oneAds['location'])){
				    			$arrAdsCollapseByDeviceAndGroupAndChannel[$oneDeviceID][$oneAds['ads_group']][$oneChannel][] = $ads_id;
				    		}
				    	}
				    }
			    }
			
			}
		
		}

		unset($arrAdGroupDef, $campaignPartners);
		
		$cacheTime = 600; //十分钟缓存
		
		//设置全部广告ID缓存
		$re = $oRedis->delete(ZK_ADS_CACHE_ALL_ADS_SET);
		foreach ($arrAdsAll as $oneAdsID){
			$re = $oRedis->sAdd(ZK_ADS_CACHE_ALL_ADS_SET, $oneAdsID);
		}
		$re = $oRedis->setTimeout(ZK_ADS_CACHE_ALL_ADS_SET, $cacheTime);
		
		foreach ($arrAds58 as $adsID){
			$oRedis->sAdd(ZK_ADS_CACHE_58_ADS_SET, $adsID);
		}
		$oRedis->setTimeout(ZK_ADS_CACHE_58_ADS_SET, $cacheTime);

		//按喜好分类设置缓存
		// 	foreach ($arrFavCache as $favID => $fav){
		// 		if(!isset($fav['ads_ids']) || empty($fav['ads_ids'])){
		// 			$fav['ads_ids'] = array();
		// 		}
		
		// 		$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_FAV_ADS_SET.$favID);		//先删除
		// 		foreach ($fav['ads_ids'] as $oneAdsID){
		// 			$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_FAV_ADS_SET.$favID, $oneAdsID);
		// 		}
		// 		$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_FAV_ADS_SET.$favID, 330);
		// 	}
		
		
		//按平台设置缓存
		// 	foreach ($arrAdsCollapseByDevice as $deviceID => $device){
		
		// 		$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_DEVICE_ADS_SET.$deviceID);	//先删除
		// 		foreach ($device as $oneAdsID){
		// 			$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_DEVICE_ADS_SET.$deviceID, $oneAdsID);
		// 		}
		// 		$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_DEVICE_ADS_SET.$deviceID, 330);
		
		// 	}

		//按平台、分组、喜好设置缓存
		$arrDeleteKeys = array();
		foreach ($arrAdsCollapseByDeviceAndGroupAndFav as $deviceID => $arrDeviceAds){
		
			foreach ($arrDeviceAds as $adsGroup => $arrDeviceGroupAds){
		
				foreach ($arrDeviceGroupAds as $favID => $arrAdsIds){
		
					//先删除
					if(!in_array(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$deviceID.$adsGroup.$favID, $arrDeleteKeys)){
						$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$deviceID.$adsGroup.$favID);
						array_push($arrDeleteKeys, ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$deviceID.$adsGroup.$favID);
					}
		
					foreach ($arrAdsIds as $oneAdsID){
						$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$deviceID.$adsGroup.$favID, $oneAdsID);
					}
					$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_FAV_ADS_SET.$deviceID.$adsGroup.$favID, $cacheTime);
				}
					
			}
		
		}
		
		//按平台、分组、地域设置缓存
		$arrDeleteKeys = array();
		foreach ($arrAdsCollapseByDeviceAndGroupAndLocation as $deviceID => $arrDeviceAds){
		
			foreach ($arrDeviceAds as $adsGroup => $arrDeviceGroupAds){
		
				foreach ($arrDeviceGroupAds as $location => $arrAdsIds){
		
					//先删除
					if(!in_array(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$deviceID.$adsGroup.$location, $arrDeleteKeys)){
						$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$deviceID.$adsGroup.$location);
						array_push($arrDeleteKeys, ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$deviceID.$adsGroup.$location);
					}
		
					foreach ($arrAdsIds as $oneAdsID){
						$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$deviceID.$adsGroup.$location, $oneAdsID);
					}
					$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_LOCATION_ADS_SET.$deviceID.$adsGroup.$location, $cacheTime);
				}
		
			}
		
		}
		
		//按平台、分组、标签设置缓存
		$arrDeleteKeys = array();
		foreach ($arrAdsCollapseByDeviceAndGroupAndTag as $deviceID => $arrDeviceAds){
		
			foreach ($arrDeviceAds as $adsGroup => $arrDeviceGroupAds){
		
				foreach ($arrDeviceGroupAds as $tag => $arrAdsIds){
		
					//先删除
					if(!in_array(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$deviceID.$adsGroup.$tag, $arrDeleteKeys)){
						$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$deviceID.$adsGroup.$tag);
						array_push($arrDeleteKeys, ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$deviceID.$adsGroup.$tag);
					}
		
					foreach ($arrAdsIds as $oneAdsID){
						$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$deviceID.$adsGroup.$tag, $oneAdsID);
					}
					$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_TAG_ADS_SET.$deviceID.$adsGroup.$tag, $cacheTime);
				}
		
			}
		
		}
		
	    //按平台、分组、频道设置缓存
		$arrDeleteKeys = array();
		if($arrAdsCollapseByDeviceAndGroupAndChannel){
		    foreach ($arrAdsCollapseByDeviceAndGroupAndChannel as $deviceID => $arrDeviceAds){
		    
		    	foreach ($arrDeviceAds as $adsGroup => $arrDeviceGroupAds){
		    
		    		foreach ($arrDeviceGroupAds as $channel => $arrAdsIds){
		    
		    			//先删除
		    			if(!in_array(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$deviceID.$adsGroup.$channel, $arrDeleteKeys)){
		    				$re = $oRedis->delete(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$deviceID.$adsGroup.$channel);
		    				array_push($arrDeleteKeys, ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$deviceID.$adsGroup.$channel);
		    			}
		    
		    			foreach ($arrAdsIds as $oneAdsID){
		    				$re = $oRedis->sAdd(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$deviceID.$adsGroup.$channel, $oneAdsID);
		    			}
		    			$re = $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_DEVICE_SINGLE_GROUP_SINGLE_CHANNEL_ADS_SET.$deviceID.$adsGroup.$channel, $cacheTime);
		    		}
		    
		    	}
		    
		    }
	    }

		zk_ads_add_log(count($arrAdsCollapseByDeviceAndGroupAndFav), 'arrAdsCollapseByDeviceAndGroupAndFav_num');
		zk_ads_add_log(count($arrAdsCollapseByDeviceAndGroupAndLocation), 'arrAdsCollapseByDeviceAndGroupAndLocation_num');
		zk_ads_add_log(count($arrAdsCollapseByDeviceAndGroupAndTag), 'arrAdsCollapseByDeviceAndGroupAndTag_num');
		zk_ads_add_log(count($arrAdsCollapseByDeviceAndGroupAndChannel), 'arrAdsCollapseByDeviceAndGroupAndChannel_num');

	    unset($arrAdsDef, $resAdGroupDef, $creatives, $creativesMap);
	    unset($arrAdsCollapseByDeviceAndGroupAndFav, $arrAdsCollapseByDeviceAndGroupAndLocation, $arrAdsCollapseByDeviceAndGroupAndTag, $arrAdsCollapseByDeviceAndGroupAndChannel);

		//执行以上所有的Redis命令
		$oRedis->exec();
		zk_ads_add_log("time: ".microtime(true), 'preload_dimensionality_cache');

		//更新广告的曝光数和点击数缓存
		zk_ads_cache_ads_show_count_and_click_count_set($adsIds);
		zk_ads_add_log("time: ".microtime(true), 'preload_ads_stats_cache');

		//更新广告计划的曝光数和点击数缓存
		zk_ads_cache_ads_show_count_and_click_count_set($adGroupIds, 'campaign');
		zk_ads_add_log("time: ".microtime(true), 'preload_campaign_stats_cache');

		//删除状态不正常的广告缓存
		zk_ads_cache_delete_unnormal_ads();
		zk_ads_add_log("time: ".microtime(true), 'delete_unnormal_ads');

		//更新创意的曝光数和点击数缓存
		refreshCreativeCache($creativeIds);
		zk_ads_add_log("time: ".microtime(true), 'refreshCreativeCache');

		//重建广告主缓存
		rebuildAdvertiserCache();
		zk_ads_add_log("time: ".microtime(true), 'rebuildAdvertiserCache');

		return TRUE;
	}
}

/**
* 轮换选择推荐的广告，对同一个用户的每次请求推荐不同的广告
* @param <string> $adGroup 广告位置
* @param <string> $udid 用户的udid
* @param <array> $arrAdsID 多个广告ID数组
* 
* @return $recommendAdID <string> 推荐的广告ID
*/
if(!function_exists('zk_ads_recommended_ads_rotate')){
	function zk_ads_recommended_ads_rotate($adGroup, $udid, $arrAdsID){
		if(!$udid || empty($arrAdsID)){
			return null;
		}
		$redisKey = md5(ZK_ADS_CACHE_USER_VIEWED_ADS.$adGroup.'_'.$udid);
		$expiredTime = 3600*24;
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_set');
		if(FALSE == $isRedisConnected){
			return null;
		}
		$status = 'ads_rotate';
		try {
			$viewedAds = $oRedis->sMembers($redisKey);
			if(!$viewedAds){
				$recommendAdID = $arrAdsID[0];
				$oRedis->sAdd($redisKey, $recommendAdID);	   //将广告ID放入已看过的广告集合
				$oRedis->setTimeout($redisKey, $expiredTime);  //设置有效期为一天
			}else{
				//去除已经看过的广告
				$notViewedAds = array_values(array_diff($arrAdsID, $viewedAds));
				if(!empty($notViewedAds)){
					//如果有没看过的广告，则选择没看过的广告里排名第一的广告。
					$recommendAdID = $notViewedAds[0];
					$oRedis->sAdd($redisKey, $recommendAdID);
				}else{
					//如果推荐的广告都看过了，则选择排名第一广告，同时清空访问过的广告集合。
					$status = 'ads_rotate_finished';
					$recommendAdID = $arrAdsID[0];
					$oRedis->delete($redisKey);
					$oRedis->sAdd($redisKey, $recommendAdID);      //将广告ID放入已看过的广告集合
					$oRedis->setTimeout($redisKey, $expiredTime);  //设置有效期为一天
				}
			}
		} catch (Exception $e) {
				
		}

		return array($recommendAdID, $status);
	}
}

/**
 * 重建广告主缓存
 * @return [type] [description]
 */
 function rebuildAdvertiserCache(){
 	require_once dirname(__FILE__).'/../classes/dsp/dsp.class.php';
 	$dsp=new Dsp(array());
 	return $dsp->rebuildAdvertiserCache();
 }

 /**
  * 刷新创意缓存
  * @param  array $creativeIds 创意ID
  * @return void
  */
 function refreshCreativeCache($creativeIds){
 	$creativeStats=zk_ads_db_creative_stat_get($creativeIds,false);
	$today=date('Y-m-d');
	if($creativeStats){
		try{
		    $creativeShowClicks=array();
		     list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		    if(!$isRedisConnected){
		    	return false;
		    }
		    //使用Redis pipeline，减少交互次数，提升效率
			$oRedis = $oRedis->multi(Redis::PIPELINE);

		    foreach ($creativeStats as $key => $creative) {
		    	$creativeShowKey= zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_SHOW_COUNT.$creative['creativeid'], 'cs_', 16);
		    	$creativeClickKey= zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_CLICK_COUNT.$creative['creativeid'], 'cc_', 16);
		    	//$creativeShowClicks[$creativeShowKey]=$creative['showCount'];
		    	$oRedis->setex($creativeShowKey,ZK_ADS_ADS_CACHE_EXPIRE,$creative['showCount']);
		    	//$creativeShowClicks[$creativeClickKey]=$creative['clickCount'];
		    	$oRedis->setex($creativeClickKey,ZK_ADS_ADS_CACHE_EXPIRE,$creative['clickCount']);
   
		    	//创意今日曝光量,缓存3天的曝光和统计
		    	if($creative['daily_shows']&&is_array($creative['daily_shows'])){
		    		$tmpI=0;
		    		foreach ($creative['daily_shows'] as $key => $value) {
		    		  if($tmpI>2){
		    		  	break;
		    		  }
		    	      $dailyShowKey= zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_SHOW_COUNT.$creative['creativeid'].'_'.$key, 'cs_', 16);
		    	      $oRedis->setex($dailyShowKey, ZK_ADS_ADS_CACHE_EXPIRE,$value);
		    		}
		    	}
		    	//创意今日点击量
		    	if($creative['daily_clicks']&&is_array($creative['daily_clicks'])){
		    		$tmpI=0;
		    		foreach ($creative['daily_clicks'] as $key => $value) {
		    		  if($tmpI>2){
		    		    break;
		    		  }
		    		  $dailyClickKey= zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_CLICK_COUNT.$creative['creativeid'].'_'.$key, 'cc_', 16);
		    		  $oRedis->setex($dailyClickKey, ZK_ADS_ADS_CACHE_EXPIRE,$value);
		    		}
		    	}
		    }
		    $oRedis->exec();
		    
	    }catch(Exception $e){

	    }
	}
 }
 /**
  * CPM广告扣费累加
  * @param  array $adInfo 
  * @return boolean         
  */
 function cpmAdvertiserCostIncr($adInfo){
    if(!$adInfo||$adInfo['deliver_type']!=2){
    	return false;
    }
    $bidding=(float)$adInfo['prize_weight'];
 
    list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
			
	if(!$isRedisConnected){
		return false;
	}
	
	$key=ZK_ADS_CACHE_CPM_COST.$adInfo['aid'];
	//单位为分,做减法时精准
	return $oRedis->incrByFloat($key,$bidding*100);
 }

  
if(!function_exists('zk_get_ads_info')){
	
	/**
	 * 获取广告缓存信息
	 * @param string $id
	 * @param string $creativeid
	 * @return array
	 */
	function zk_get_ads_info($id,$creativeid=''){
		if(!$id){
			return array();
		}
		$keyRedis = ZK_ADS_CACHE_SINGLE_ADS_DEF.strval($id);
		
 		
		$data = array();
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		 
		if($isRedisConnected){
			try {
				$info = $oRedis->get($keyRedis);
				if(empty($info)){
					return array();
				}
				$data = json_decode($info,true);
				if(empty($data)){
					return array();
				}
				if($creativeid){
					$data['creativeid']=$creativeid;
					if(!empty($data['creatives'][$creativeid]['title'])){
						$data['ads_content'] = $data['creatives'][$creativeid]['title'];
					}
					if(!empty($data['creatives'][$creativeid]['ads_pic'])){
						$data['ads_pic'] = $data['creatives'][$creativeid]['ads_pic'];
					}
					if(!empty($data['creatives'][$creativeid]['ads_short_pic'])){
						$data['ads_short_pic'] = $data['creatives'][$creativeid]['ads_short_pic'];
					}
					if(!empty($data['creatives'][$creativeid]['multi_pics'])){
						$data['multi_pics'] = $data['creatives'][$creativeid]['multi_pics'];
					}
				}
				$data['ads_pic'] = str_replace('adpic', 'pic1', $data['ads_pic']);
				$data['ads_short_pic'] = str_replace('adpic', 'pic1', $data['ads_short_pic']);
			} catch (Exception $e) {
				
			}
		}

		return $data;
	}
	
}

/**
 * by lane
 * 获取指定渠道用户 的广告计划输出名单
 */
if(!function_exists('zk_getDeviceidCampaignList')){
	function zk_getDeviceidCampaignList($deviceid,$type='show'){
		$db_ = db_mongoDB_conn(ZK_MONGO_TB_ZK_DSP_CAMPAIGN_OUTPUT);
		if(empty($deviceid)){
			return false;
		}

		$db_->where(array('deviceid'=>$deviceid,'type'=>$type));
		$return_arr =array();
		$list = $db_->getOne(ZK_MONGO_TB_ZK_DSP_CAMPAIGN_OUTPUT);

		if(!empty($list['campaignSpaceIds'])){
			$return_arr = $list['campaignSpaceIds'];
		}
		return $return_arr;
	}
}

/**
* 获取开放给合作方（如魅族）的广告计划
*/
if(!function_exists('zk_get_campaign_ids_by_device')){
	function zk_get_campaign_ids_by_device($deviceid, $type='show'){
		$db_ = db_mongoDB_conn(ZK_MONGO_TB_ZK_DSP_CAMPAIGN_OUTPUT);
		if(empty($deviceid)){
			return false;
		}

		$db_->where(array('deviceid'=>$deviceid,'type'=>$type));
		$return_arr =array();
		$list = $db_->getOne(ZK_MONGO_TB_ZK_DSP_CAMPAIGN_OUTPUT);

		if(!empty($list['campaignids'])){
			$return_arr = $list['campaignids'];
		}
		return $return_arr;
	}
}

/**
 * by lane
 * 更新指定渠道用户 的广告计划输出名单的缓存
 */
if(!function_exists('zk_updateDeviceidCampaignList_cache')){
	function zk_updateDeviceidCampaignList_cache($deviceid,$type='show',$data){

		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(TRUE == $isRedisConnected){
			try {
				$data = gzencode(json_encode($data), 9);
				$oRedis->set(ZK_REDIS_KEY_DSP_CAMPAIGN_OUTPUT.$deviceid."_".$type, $data);
				$oRedis->setTimeout(ZK_REDIS_KEY_DSP_CAMPAIGN_OUTPUT.$deviceid."_".$type, 86400*7);
				return true;
			} catch (Exception $e) {

			}
		}
		return false;
	}
}
/**
 * by lane
 * 获取指定渠道用户 的广告计划输出名单缓存（无缓存则生成）
 */
if(!function_exists('zk_getDeviceidCampaignList_cache')){
	function zk_getDeviceidCampaignList_cache($deviceid,$type='show',$is_cache=true){

		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		$r = false;
		$cache_exists=false;
		if(TRUE == $isRedisConnected && $is_cache==true){
			try {
				$r = $oRedis->get(ZK_REDIS_KEY_DSP_CAMPAIGN_OUTPUT.$deviceid."_".$type);

				if($r !== false) {
					$r = json_decode(zk_gzdecode($r), true);
					
					$cache_exists=true;
				}
			} catch (Exception $e) {

			}
		}

		if(function_exists('zk_updateDeviceidCampaignList_cache') && function_exists('zk_getDeviceidCampaignList') && $cache_exists ==false){
			$r = zk_getDeviceidCampaignList($deviceid,$type);
				
			zk_updateDeviceidCampaignList_cache($deviceid,$type,$r);
		}

		return $r;

	}
}

/**
 * 图片换成zkres3地址
 */
if(!function_exists('zk_ads_fix_ad_img_url')){
	function zk_ads_fix_ad_img_url($img_url){

		if(strpos($img_url, 'zkres.myzaker.com/') > 0){
			$img_url = str_replace("zkres.myzaker.com/", "zkres3.myzaker.com/", $img_url);
		}elseif(strpos($img_url, 'upload.myzaker.com/data/') > 0){
			$img_url = str_replace("upload.myzaker.com/data/", "zkres3.myzaker.com/data/", $img_url);
		}

		return $img_url;
	}
}

if(!function_exists('zk_ads_topic_ad')){
	/**
	 * 查找专题列表的广告
	 * @param array $oReq
	 * @return array
	 */
 	function zk_ads_topic_ad($oReq, $pageSize=10){

 		zk_ads_add_log($oReq, 'REQUEST');
 		$arrAds = array();
 		if(ZK_ADS_IS_SHOW_ADS == 0){
 			return array();
 		}
 		list($recommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('topic_list',$oReq);
 		//echo '<pre>';
 		//var_dump($arrAdsDetails);
 		if(empty($arrAdsScore)){
 			return $arrAds;
 		}
 		$advertiserIds = array();
 		foreach ($arrAdsScore as $ad){
 			$adsID = $ad['ads_id'];
 			$oneAds = $arrAdsDetails[$adsID];
 			if(!$oneAds){
 				continue;
 			}
 			if($oneAds['deliver_type'] != 1){ //只需要CPC广告
 				continue; 
 			}
 			if(in_array($oneAds['aid'], $advertiserIds)){
 				continue; //保证同一个广告主的广告只出现一次
 			}
 			$advertiserIds[] = $oneAds['aid'];

 			$arrAds[] = $oneAds;
 		}
 		$arrAds = array_slice($arrAds, 0, $pageSize);
 		return $arrAds;
 	}
}

if(!function_exists('zk_ads_topic_ad_format')){
	/**
	 * 专题广告格式
	 * @param <array> $ads
	 * @param <array> $oReq
	 * @return array
	 */
	function zk_ads_topic_ad_format($ads,$oReq){

		/**
		pk: "54213e849490cb452d0000aa",
        title: "Kindle电子书，只为阅读而生",
        date: "2014-09-24 09:45:00",
        auther_name: "科技频道",
        weburl: "http://iphone.myzaker.com/l.php?l=54213e849490cb452d0000aa",
        is_full: "NO",
        full_arg: "_appid",
        type: "web2",
        special_info: {
          open_type: "web",
          need_user_info: "N",
          web_url: "http://iphone.myzaker.com/zaker/ad_article.php?_id=54213e849490cb452d0000aa&title=Kindle%E7%94%B5%E5%AD%90%E4%B9%A6%EF%BC%8C%E5%8F%AA%E4%B8%BA%E9%98%85%E8%AF%BB%E8%80%8C%E7%94%9F&open_type=web&_appid=iphone&need_userinfo=N&url=http%3A%2F%2Fad.doubleclick.net%2Fddm%2Ftrackclk%2FN9254.1131587.REDLOOP%2FB8242851.111036595%3Bdc_trk_aid%3D284048495%3Bdc_trk_cid%3D59204485",
          icon_url: "http://zkres.myzaker.com/data/image/mark/ad_2x.png",
          show_jingcai: "Y"
        },
        special_type: "tag",
        full_url: "http://iphone.myzaker.com/zaker/article_mongo_nocache.php?app_id=13&pk=54213e849490cb452d0000aa&ad=1",
        is_ad: "Y"
		 */
		
		$ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);
		if(strpos(" ".$ads['ads_link_url'], "zkopenthirdapp://") > 0){
			$ad_stat_url = zk_ads_format_ad_stat_url($ads, $oReq); //点击统计页
		}
		$icon_url = "http://zkres3.myzaker.com/data/image/mark2/ad_2x.png";
		
		$arrAds = array();
		$arrAds['pk'] = strval($ads['_id']);
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['thumbnail_title'] = $arrAds['title'];
		$arrAds['date'] = "";
		$arrAds['auther_name'] = preg_replace('/\(.*\)|（.*）/i', '', $ads['sponsor']);//广告主名称
		
		if(empty($ads['zk_pk'])) {
			$arrAds['weburl'] = $ads['ads_link_url'];
			$arrAds['type'] = "web2";
		}

		$arrAds['is_full'] = "NO";
		$arrAds['full_arg'] = "_appid";
		$arrAds['special_info'] = array(
				'open_type' => "web",
				'need_user_info' => "Y",
				'web_url' => $ad_link_url,
				'icon_url' => zk_ads_change_http_prefix($icon_url, $oReq['http_type']),
				'show_jingcai' => "Y",
		);

		if(!empty($ads['ads_short_pic'])){
			$arrAds['special_info']['item_type'] = '1'; //图文混合类型
			$arrAds['thumbnail_medias'] = array(
				array(
					'type' => "image",
					'url' => $ads['ads_short_pic'],
					'm_url' => $ads['ads_short_pic'],
					'raw_url' => $ads['ads_short_pic'],
				)
			);
		}

		if($_REQUEST['_version'] >= 6.1 || ($oReq['_appid'] == 'ipad' && $oReq['_version'] >= 3.3)){
			$arrAds['special_info']['stat_read_url'] = zk_ads_format_ad_show_url($ads,$oReq);
			if(!empty($ad_stat_url)){
				$arrAds['special_info']['stat_click_url'] = $ad_stat_url;
			}
		}
		if($oReq['_version'] >= 7.94){
			//7.9.4及之后版本需要支持多个数据统计地址，曝光、点击统计url改成数组。
			unset($arrAds['special_info']['stat_read_url'], $arrAds['special_info']['stat_click_url']);
			$arrAds['special_info']['need_user_info'] = 'N';
			$arrAds['special_info']['web_url'] = $ads['ads_link_url'];
			$arrAds['special_info']['dsp_stat_info']['show_stat_urls'] = zk_ads_get_show_stat_urls($ads, $oReq);
			$arrAds['special_info']['dsp_stat_info']['click_stat_urls'] = zk_ads_get_click_stat_urls($ads, $oReq);
		}

		$arrAds['special_type'] = "tag";

		if(!empty($ads['zk_pk'])){
			//积分商城等文案类型的“广告”
			load_helper('zkcmd');
			$ads['ads_link_url'] = zkopen_article($ads['zk_pk']);
		}else{
			$arrAds['full_url'] = "";
		}

		$arrAds['is_ad'] = "Y";
		
		//打开外部浏览器
		if($ads['web_target'] == 'safari'){
			$arrAds['type'] = "other";
			$arrAds['special_info']['open_type'] = 'safari';
		}elseif (!empty($ads['loading_text'])){
			$arrAds['special_info']['web_show_arg']['loading_text'] = $ads['loading_text'];
		}

		$advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		if(in_array($ads['aid'], $advertiserWithoutAdTag)){
			$arrAds['special_info']['icon_url'] = '';
		}

		//如果广告落地页是app内部地址，则需要按app内部格式拼装
		if(is_app_inner_url($ads['ads_link_url'])){
			zk_ads_format_app_inner_content($arrAds, $ads['ads_link_url'], $oReq);
		}
		
		return $arrAds;

	}

}

if(!function_exists('zk_ads_discuss_ad')){
    /**
     * 查找评论区的广告
     * @param array $oReq
     * @return array
     */
    function zk_ads_discuss_ad($oReq, $pageSize=10){

        zk_ads_add_log($oReq, 'REQUEST');
        $arrAds = array();
        if(ZK_ADS_IS_SHOW_ADS == 0){
            return array();
        }
        list($recommendAdsID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad('discuss_banner',$oReq);
        //echo '<pre>';
        //var_dump($arrAdsDetails);
        if(empty($arrAdsScore)){
            return $arrAds;
        }
        $advertiserIds = array();
        foreach ($arrAdsScore as $ad){
            $adsID = $ad['ads_id'];
            $oneAds = $arrAdsDetails[$adsID];
            if(!$oneAds){
                continue;
            }
            if($oneAds['deliver_type'] != 1){ //只需要CPC广告
                continue;
            }
            if(in_array($oneAds['aid'], $advertiserIds)){
                continue; //保证同一个广告主的广告只出现一次
            }
            $advertiserIds[] = $oneAds['aid'];

            $arrAds[] = $oneAds;
        }
        $arrAds = array_slice($arrAds, 0, $pageSize);
        return $arrAds;
    }
}

if(!function_exists('zk_ads_discuss_ad_format')){
    /**
     * 评论区广告格式
     * @param <array> $ads
     * @param <array> $oReq
     * @return array
     */
    function zk_ads_discuss_ad_format($ads,$oReq){

        /**
        pk: "54213e849490cb452d0000aa",
        title: "Kindle电子书，只为阅读而生",
        date: "2014-09-24 09:45:00",
        auther_name: "科技频道",
        weburl: "http://iphone.myzaker.com/l.php?l=54213e849490cb452d0000aa",
        is_full: "NO",
        full_arg: "_appid",
        type: "web2",
        special_info: {
        open_type: "web",
        need_user_info: "N",
        web_url: "http://iphone.myzaker.com/zaker/ad_article.php?_id=54213e849490cb452d0000aa&title=Kindle%E7%94%B5%E5%AD%90%E4%B9%A6%EF%BC%8C%E5%8F%AA%E4%B8%BA%E9%98%85%E8%AF%BB%E8%80%8C%E7%94%9F&open_type=web&_appid=iphone&need_userinfo=N&url=http%3A%2F%2Fad.doubleclick.net%2Fddm%2Ftrackclk%2FN9254.1131587.REDLOOP%2FB8242851.111036595%3Bdc_trk_aid%3D284048495%3Bdc_trk_cid%3D59204485",
        icon_url: "http://zkres.myzaker.com/data/image/mark/ad_2x.png",
        show_jingcai: "Y"
        },
        special_type: "tag",
        full_url: "http://iphone.myzaker.com/zaker/article_mongo_nocache.php?app_id=13&pk=54213e849490cb452d0000aa&ad=1",
        is_ad: "Y"
         */

        $ad_link_url = zk_ads_format_ad_link_url($ads, $oReq);

        $arrAds = array();
        $arrAds['pk'] = strval($ads['_id']);
        $arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
        $arrAds['thumbnail_title'] = $arrAds['title'];
        $arrAds['date'] = "";
        $arrAds['auther_name'] = "";
        $arrAds['weburl'] = $ads['ads_link_url'];
        $arrAds['is_full'] = "NO";
        $arrAds['full_arg'] = "_appid";
        $arrAds['type'] = "web2";
        $arrAds['special_info'] = array(
            'open_type' => "web",
            'need_user_info' => "Y",
            'web_url' => $ad_link_url,
            'icon_url' => "http://zkres3.myzaker.com/data/image/mark2/ad_2x.png",
            //'show_jingcai' => "Y",
        );

        $advertiserWithoutAdTag = zk_ads_config('advertiser_without_ad_tag');  //不需要广告标签的广告主
		if(in_array($ads['aid'], $advertiserWithoutAdTag)){
			$arrAds['special_info']['icon_url'] = '';
		}

        if(!empty($ads['ads_pic'])){
            //$arrAds['special_info']['item_type'] = '1'; //图文混合类型
            $arrAds['thumbnail_medias'] = array(
                array(
                    'type' => "image",
                    'url' => $ads['ads_pic'],
                    'm_url' => $ads['ads_pic'],
                    'raw_url' => $ads['ads_pic'],
                )
            );
        }

        if($_REQUEST['_version'] >= 6.1 || ($oReq['_appid'] == 'ipad' && $oReq['_version'] >= 3.3)){
            $arrAds['special_info']['stat_read_url'] = zk_ads_format_ad_show_url($ads,$oReq);
        }

        $arrAds['special_type'] = "tag";
        $arrAds['full_url'] = "";
        $arrAds['is_ad'] = "Y";

        //打开外部浏览器
        if($ads['web_target'] == 'safari'){
            $arrAds['type'] = "other";
            $arrAds['special_info']['open_type'] = 'safari';
        }elseif (!empty($ads['loading_text'])){
            $arrAds['special_info']['web_show_arg']['loading_text'] = $ads['loading_text'];
        }

        return $arrAds;

    }

}

/**
* 解压数据
*/
if(!function_exists('zk_gzdecode')){
	function zk_gzdecode ($data) {
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;

		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}

		if ($flags & 8) // Filename
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 16) // Comment
			$headerlen = strpos($data, chr(0), $headerlen) + 1;
		if ($flags & 2) // CRC at end of file
			$headerlen += 2;

		$unpacked = gzinflate(substr($data, $headerlen));

		if ($unpacked === FALSE)
			$unpacked = $data;

		return $unpacked;
	}
}

/**
 * 对外合作接口查找广告
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_find_ads_for_mycheering')){
	function zk_ads_find_ads_for_mycheering($oReq){
		list($recommendAdID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad($oReq['ads_group'],$oReq);
		if(empty($arrAdsScore)){
			return array();
		}
	    foreach ($arrAdsScore as $ad){
	        $adsID = $ad['ads_id'];
	        $oneAds = $arrAdsDetails[$adsID];
	        if(!$oneAds){
	            continue;
	        }
	        if($oneAds['deliver_type'] != 1){  //只返回CPC广告
	            continue;
	        }
	        //判断zkopenthirdapp打开方式
	        if(strpos($oneAds['ads_link_url'], "zkopenthirdapp://") === 0){
	            continue;
	        }
	        if($_GET['ads_group'] == 'landscape' || $_GET['ads_group'] == 'third_large'){
	        	//只需要大图(640x360)广告
		        if(empty($oneAds['ads_pic'])){
		            continue;
		        }
	    	}elseif($_GET['ads_group'] == 'news_feed' || $_GET['ads_group'] == 'third_small'){
	    		//只需要图文混合广告
		        if(empty($oneAds['ads_content']) || empty($oneAds['ads_short_pic'])){
		            continue;
		        }
	    	}elseif($_GET['ads_group'] == 'text_link' || $_GET['ads_group'] == 'third_text'){
	    		//只需要纯文字广告
	    		if(empty($oneAds['ads_content'])){
		            continue;
		        }
	    	}elseif($_GET['ads_group'] == 'three_images'){
	    		//只需三图样式广告
	    		if(empty($oneAds['multi_pics'])){
		            continue;
		        }
	    	}
	        $arrAds[] = $oneAds;
	    }  
	    if(!empty($arrAds)){
			//记录广告响应次数
			zk_ads_cache_channel_response_count_incr($oReq['device_id'], $oReq['ads_group']);
		}
	    return $arrAds;   
	}
}

/**
 * 查找广告 为 zk_special_outward_gg 接口
 */
if(!function_exists('zk_ads_find_ads_for_special')){
    function zk_ads_find_ads_for_special($oReq) {
        list($recommendAdID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad($oReq['ads_group'], $oReq);
        if (empty($arrAdsScore)) {
            return array();
        }
        foreach ($arrAdsScore as $ad) {
            $adsID = $ad['ads_id'];
            $oneAds = $arrAdsDetails[$adsID];
            if (!$oneAds) {
                continue;
            }

            if ($oReq['f'] != 'bgll') {
                if ($oneAds['deliver_type'] != 1) {  //只返回CPC广告
                    zk_ads_add_log($adsID, 'NONE_CPC');
                    continue;
                }
                if (empty($oneAds['output_partners']) || !in_array($oReq['f'], $oneAds['output_partners'])) {
                    zk_ads_add_log($adsID,'NONE_OUTPUT_PARTNERS');
                    continue;
                }

            } else {
                if (empty($oneAds['special_output_partners']) || $oneAds['special_output_partners'] != $oReq['f']) {
                    zk_ads_add_log($adsID,'NONE_SPECIAL_OUTPUT_PARTNERS');
                    continue;
                }
            }

            //判断zkopenthirdapp打开方式
            if (strpos($oneAds['ads_link_url'], "zkopenthirdapp://") === 0) {
                continue;
            }
            if ($_GET['ads_group'] == 'landscape' || $_GET['ads_group'] == 'third_large') {
                //只需要大图(640x360)广告
                if (empty($oneAds['ads_pic'])) {
                    continue;
                }
            } elseif ($_GET['ads_group'] == 'news_feed' || $_GET['ads_group'] == 'third_small') {
                //只需要图文混合广告
                if (empty($oneAds['ads_content']) || empty($oneAds['ads_short_pic'])) {
                    continue;
                }
            } elseif ($_GET['ads_group'] == 'text_link' || $_GET['ads_group'] == 'third_text') {
                //只需要纯文字广告
                if (empty($oneAds['ads_content'])) {
                    continue;
                }
            } elseif ($_GET['ads_group'] == 'three_images') {
                //只需三图样式广告
                if (empty($oneAds['multi_pics'])) {
                    continue;
                }
            }

            zk_ads_add_log('[' . $oneAds['_id'] . '] [' . $oneAds['ads_name'] . '], output_percent:' . $oneAds['output_percent'] . ',target_click:' . $target_clicks, 'ALL_AVAILABLE');

            list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
            $clickKey = md5("ads_click_count_from_outward_" . date('Ymd') . "_" . $oneAds['ad_group_id']); //广告计划今天的点击数
            $currentClick = intval($oRedis->get($clickKey));
            $showKey = md5("ads_show_count_from_outward_" . date('Ymd') . "_" . $oneAds['ad_group_id']); //广告计划今天的曝光数
            $currentShow = intval($oRedis->get($showKey));
            $oneAds['current_click'] = $currentClick;
            $oneAds['current_show'] = $currentShow;

            if ($oReq['f'] != 'bgll') {
                //最大点击数
                $target_clicks = $oneAds['target_clicks'];
                if (!empty($oneAds['daily_target_clicks'])) {
                    $target_clicks = $oneAds['daily_target_clicks'];
                }
                //没有设置output默认100
                if (empty($oneAds['output_percent'])) {
                    $oneAds['output_percent'] = 100;
                }

                //如果设置了最大占比
                if ($target_clicks > 0) {
                    //最大点击数
                    $clicksMax = intval((intval($oneAds['output_percent']) / 100) * $target_clicks);
                    // 广告计划今天的曝光数
                    $showCountToday = zk_ads_cache_ads_show_count_get($ad['ad_group_id'] . "_" . date('Y-m-d'));

                    //如果已经超过最大点击数，过滤掉次广告，或者广告计划今日曝光小于500
                    if ($currentClick >= $clicksMax || $showCountToday < 500) {
                        zk_ads_add_log('点击超过上限，或者，曝光过小还不能显示' . '[' . $oneAds['_id'] . '] [' . $oneAds['ads_name'] . '], output_percent:' . $oneAds['output_percent'] . ',target_click:' . $target_clicks . ', 刷量点击上限:' . $clicksMax . ',今日刷量点击:' . $currentClick . ',今日曝光:' . $showCountToday, 'CLICK_COUNT_UNMORMAL');
                        continue;
                    } else {
                        zk_ads_add_log('[' . $oneAds['_id'] . '] [' . $oneAds['ads_name'] . '], output_percent:' . $oneAds['output_percent'] . ',target_click:' . $target_clicks . ', 刷量点击上限:' . $clicksMax . ',今日刷量点击:' . $clickCount . ', 今日刷量曝光:' . $currentShow . ',show_count_today:' . $showCountToday, 'FINAL_RETURN_OUTWARD');
                    }
                }
            } else {
                $oneAds['click_continue'] = false;
                $oneAds['show_continue'] = false;
                if (!empty($oneAds['special_output_partners_click'])) {
                    if ($currentClick >= $oneAds['special_output_partners_click']) {
                        zk_ads_add_log("点击超过上限[{$oneAds['_id']}][{$oneAds['ads_name']}], special_output_partners_click:{$oneAds['special_output_partners_click']}, current_click:{$currentClick}");
                        $oneAds['click_continue'] = true;
                    }
                }

                if (!empty($oneAds['special_output_partners_show'])) {
                    if ($currentShow >= $oneAds['special_output_partners_show']) {
                        zk_ads_add_log("曝光超过上限[{$oneAds['_id']}][{$oneAds['ads_name']}], special_output_partners_click:{$oneAds['special_output_partners_show']}, current_show:{$currentShow}");
                        $oneAds['show_continue'] = true;
                    }
                }

                if (!empty($oneAds['special_output_partners_click']) && !empty($oneAds['special_output_partners_show'])) {
                    if ($oneAds['click_continue'] && $oneAds['show_continue']) {
                        continue;
                    }
                } else {
                    if ($oneAds['click_continue'] || $oneAds['show_continue']) {
                        continue;
                    }
                }
            }
            $arrAds[] = $oneAds;
        }

        if (!empty($arrAds)) {
            //记录广告响应次数
            zk_ads_cache_channel_response_count_incr($oReq['device_id'], $oReq['ads_group']);
            return $arrAds;
        } else {
            return array();
        }

    }
}

/**
 * 对外合作接口查找广告 (91黄历天气)
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_find_ads_for_91huangli')){
	function zk_ads_find_ads_for_91huangli($oReq){
		list($recommendAdID, $arrAdsScore, $arrAdsDetails) = zk_ads_find_ad($oReq['ads_group'],$oReq);
	    if(empty($arrAdsScore)){
			return array();
		}
		$unfitAds = array();
	    foreach ($arrAdsScore as $ad){
	        $adId = $ad['ads_id'];
	        $oneAds = $arrAdsDetails[$adId];
	        if(!$oneAds){
	            continue;
	        }
	        //if($oneAds['deliver_type'] != 1){  //只返回CPC广告
	            //continue;
	        //}
	       	//判断zkopenthirdapp打开方式
	        if(strpos($oneAds['ads_link_url'], "zkopenthirdapp://") === 0){
	            continue;
	        }
	        if($_GET['ads_group'] == 'article_bottom_banner'){
	        	//只需要大图+标题广告
		        if(empty($oneAds['ads_pic']) || empty($oneAds['ads_content'])){
		        	$unfitAds['no_title_or_pic'][] = $adId;
		            continue;
		        }
	    	}elseif($_GET['ads_group'] == 'news_feed'){
	    		//只需要小图+标题广告
		        if(empty($oneAds['ads_content']) || empty($oneAds['ads_short_pic'])){
		        	$unfitAds['unmatch_ads_type'][] = $adId;
		        	continue;
		        }
	    	}
	        $arrAds[] = $oneAds;
	    } 
	    zk_ads_add_log($unfitAds, 'last_step_filterd_ads');

	    if(!empty($arrAds)){
			//记录广告响应次数
			zk_ads_cache_channel_response_count_incr($oReq['device_id'], $oReq['ads_group']);
		}
	    return $arrAds;    
	}
}


/**
 * 对外合作接口通用的广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_format_for_mycheering')){
	function zk_ads_format_for_mycheering($ads,$oReq){
		$arrAds = array();
		$arrAds['show_type'] = $ads['ads_type'];
		$arrAds['start_time'] = time();
		$arrAds['end_time'] = $arrAds['start_time'] + 300;
		$arrAds['id'] = strval($ads['_id']);
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['second_title'] = strval($ads['ads_stitle']);
		$arrAds['pic'] = strval($ads['ads_pic']);
		$arrAds['short_pic'] = strval($ads['ads_short_pic']);
		$arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
		$arrAds['read_url'] = zk_ads_format_ad_show_url($ads,$oReq);

	    $textTypes = array('text_link', 'third_text', 'block_page');
		$picTextTypes = array('news_feed', 'third_small', 'article_recommend', 'topic_list');
	    if(in_array($oReq['ads_group'], $picTextTypes)) { 
	        $arrAds['show_type'] = 2;   //图文类型
	    }elseif(in_array($oReq['ads_group'], $textTypes)) { 
	        $arrAds['show_type'] = 3;   //纯文字类型
	    }else{
			$arrAds['show_type'] = 1;	//大图类型
		}
		$tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
		$arrAds['tag_pic'] = array(
			'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
			'image_height'  => 26,
			'image_width'   => 46
		);
		return $arrAds;

	}
}

/**
 * 外部导量接口广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_format_for_outward')){
	function zk_ads_format_for_outward($ads,$oReq){
		$arrAds = array();
		$arrAds['show_type'] = $ads['ads_type'];
		$arrAds['id'] = strval($ads['_id']);
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
		$arrAds['read_url'] = zk_ads_format_ad_show_url($ads,$oReq);

		if($_GET['ads_group'] == 'landscape') { 
	        $arrAds['show_type'] = 1;   //大图类型
	        $arrAds['imgs'] = array($ads['ads_pic']);
	    }
	    elseif($_GET['ads_group'] == 'news_feed') { 
	        $arrAds['show_type'] = 2;   //图文类型
	        $arrAds['imgs'] = array($ads['ads_short_pic']);
	    }
	    elseif($_GET['ads_group'] == 'text_link') { 
	        $arrAds['show_type'] = 3;   //纯文字类型
	        $arrAds['imgs'] = array();
	    }
	    elseif($_GET['ads_group'] == 'three_images') { 
	        $arrAds['show_type'] = 15;   //三图类型
	        $arrAds['imgs'] = array($ads['multi_pics']);
	    }
	    else{
			$arrAds['show_type'] = 0;	//大图类型
			$arrAds['imgs'] = array();
		}

		$tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
		$arrAds['tag_pic'] = array(
			'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
			'image_height'  => 26,
			'image_width'   => 46
		);
		return $arrAds;

	}
}


/**
 * special 导量接口广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_format_for_special')){
    function zk_ads_format_for_special($ads,$oReq){
        $arrAds = array();
        $arrAds['show_type'] = $ads['ads_type'];
        $arrAds['id'] = strval($ads['_id']);
        $arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
        $arrAds['name'] = $ads['ads_name'];
        $arrAds['click_url'] = '';
        $arrAds['show_url'] = '';
        $arrAds['target_clicks'] = $ads['daily_target_clicks'] ? $ads['daily_target_clicks'] : $ads['target_clicks'];
        $arrAds['output_percent'] = (float)0;
        $arrAds['output_radio'] = (float)0;
        $arrAds['special_output_partners_click'] = 0;
        $arrAds['special_output_partners_show'] = 0;
        $arrAds['deliver_type'] = $ads['deliver_type'];

        if ($oReq['f'] == 'bgll') {
            if ($ads['click_continue'] == false) {
                $arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
            }
            if ($ads['show_continue'] == false) {
                $arrAds['show_url'] = zk_ads_format_ad_show_url($ads,$oReq);
            }
            $arrAds['special_output_partners_click'] = $ads['special_output_partners_click'];
            $arrAds['special_output_partners_show'] = $ads['special_output_partners_show'];
        } else {
            $arrAds['output_percent'] = $ads['output_percent'];
            $arrAds['output_radio'] = $ads['output_radio'];
            $arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
            $arrAds['show_url'] = zk_ads_format_ad_show_url($ads,$oReq);
        }

        if($_GET['ads_group'] == 'landscape') {
            $arrAds['show_type'] = 1;   //大图类型
            $arrAds['imgs'] = array($ads['ads_pic']);
        }
        elseif($_GET['ads_group'] == 'news_feed') {
            $arrAds['show_type'] = 2;   //图文类型
            $arrAds['imgs'] = array($ads['ads_short_pic']);
        }
        elseif($_GET['ads_group'] == 'text_link') {
            $arrAds['show_type'] = 3;   //纯文字类型
            $arrAds['imgs'] = array();
        }
        elseif($_GET['ads_group'] == 'three_images') {
            $arrAds['show_type'] = 15;   //三图类型
            $arrAds['imgs'] = array($ads['multi_pics']);
        }
        else{
            $arrAds['show_type'] = 0;	//大图类型
            $arrAds['imgs'] = array();
        }

        $tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
        $arrAds['tag_pic'] = array(
            'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
            'image_height'  => 26,
            'image_width'   => 46
        );
        return $arrAds;

    }
}

/**
 * meizu_flyme的广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 * @return array
 */
if(!function_exists('zk_ads_format_for_meizu_flyme')){
	function zk_ads_format_for_meizu_flyme($ads,$oReq){
		$arrAds = array();
		$arrAds['show_type'] = $ads['ads_type'];
		$arrAds['start_time'] = time();
		$arrAds['end_time'] = $arrAds['start_time'] + 300;
		$arrAds['id'] = strval($ads['_id']);
		$arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
		$arrAds['second_title'] = strval($ads['ads_stitle']);
		$arrAds['pic'] = strval($ads['ads_pic']);
		$arrAds['short_pic'] = strval($ads['ads_short_pic']);
		$arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
		$arrAds['read_url'] = zk_ads_format_ad_show_url($ads,$oReq);

	    //没有版本号的就不显示大图
	    if(empty($oReq['_apiversion'])) {
	    	$arrAds['pic']='';
			if($ads['ads_type'] == 1) { 
				//如果之前是大图模式，则判断是否有小图，有就是图文混合，没有就纯文字
				$arrAds['show_type'] = $ads['ads_short_pic'] ? 2 : 3;
			}
	    }else{
	        /**
	         * 有版本号的时候
	         * 有小图就给小图
	         * 没有小图，给大图，都没有就给纯文字
	         */
	        if($ads['ads_short_pic']) {
	            $arrAds['show_type'] = 2;
	        }else{
	            $arrAds['show_type'] = $ads['ads_pic'] ? 1: 3;
	        }
	    }

	    $tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
		$arrAds['tag_pic'] = array(
			'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
			'image_height'  => 26,
			'image_width'   => 46
		);
		return $arrAds;

	}
}

/**
 * 91桌面的广告输出格式
 * @param <array> $ads 广告信息
 * @param <array> $oReq 请求信息
 *
 * 三种ads_group:
 * full_screen
 * topic_list
 * flash_screen
 *
 */
if(!function_exists('zk_ads_format_for_91zhuomian')){
	function zk_ads_format_for_91zhuomian($ads, $oReq)
	{
	    $arrAds = array();
	    $arrAds['show_type'] = 1;
	    $arrAds['start_time'] = time();
	    $arrAds['end_time'] = $arrAds['start_time'] + 300;
	    $arrAds['id'] = strval($ads['_id']);
	    $arrAds['title'] = str_replace("<br />", " ", $ads['ads_content']);
	    $arrAds['second_title'] = strval($ads['ads_stitle']);
	    $arrAds['click_url'] = zk_ads_format_ad_link_url($ads, $oReq);
	    $arrAds['read_url'] = zk_ads_format_ad_show_url_91zhuomian($ads,$oReq);

	    $image_width    = 46;
	    $image_height   = 26;
	    $tag_pic = 'http://zkres.myzaker.com/data/image/mark2/ad_2x.png?v=2015061216';
	    $arrAds['tag_pic'] = array(
	        'image_url'     => zk_ads_change_http_prefix($tag_pic, $oReq['http_type']),
	        'image_height'  => $image_height,
	        'image_width'   => $image_width
	    );

	    //ios和android原生广告演示的时候，是没有大图的
	    if(in_array($oReq['ads_group'], array('topic_list'))){
	        $arrAds['pic']=strval($ads['ads_short_pic']);
	    }elseif(in_array($oReq['ads_group'], array('full_screen', 'flash_screen'))) {
	        $arrAds['pic'] = strval($ads['ads_pic']);
	    }else{
	        $arrAds['pic']='';
	    }

	    return $arrAds;
	}
}

/**
 * 91桌面的曝光链接需要用POST，所以做一个中转页面在跳转
 */
if(!function_exists('zk_ads_format_ad_show_url_91zhuomian')){
	function zk_ads_format_ad_show_url_91zhuomian($ads ,$oReq)
	{
	    $ads_group=$ads['ads_group']?$ads['ads_group']:$oReq['ads_group'];
	    $show_url = "http://".ZK_ADS_DOMAIN."zk_ggs_show_91zhuomian.php?ads_id={$ads['_id']}&creative_id={$ads['creativeid']}&ads_group={$ads_group}&_udid={$oReq['_udid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&new_app_id={$oReq['new_app_id']}&need_user_info=Y";
	    //wap版渠道
	    $show_url .= "&f=".urlencode($oReq['f']);
	    $oReq['ads_id'] = $ads['_id'];
		$oReq['action'] = 'show';
		$oReq['ads_group'] = $ads['ads_group'];
		$show_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);
		
	    return $show_url;
	}
}

/**
 * 判断是否是iTunes的链接
 */
if(!function_exists('zk_ads_is_itunes_url')){
	function zk_ads_is_itunes_url($url)
	{
	    $pos = strpos($url, 'itunes.apple.com');
	    if(!$pos){
	    	return false;
	    }
	    return $pos < 10 ? true: false;
	}
}

/**
 * 获取zkey，用于验证
 */
if(!function_exists('zk_ads_get_zkey')){
	function zk_ads_get_zkey($params)
	{
		$zkey = 'zk_ggs_'.$params['action']. $params['f'].$params['_appid'].$params['ads_group'].$params['_udid'].$params['ads_id'].$params['now'];
		return md5($zkey);
	}
}

/**
 * 合作方广告获取zkey,用于验证。目前仅广点通使用了
 */
if(!function_exists('partner_ads_get_zkey')){
    function partner_ads_get_zkey($params, $adsId = '')
    {
        if (empty($adsId)) {
            $zkey = 'partner_ggs_'.$params['action']. $params['f'].$params['_appid'].$params['ads_group'].$params['_udid'].$params['now'].$params['rand'];
        } else {
            $zkey = 'partner_ggs_'.$params['action']. $params['f'].$params['_appid'].$params['ads_group'].$params['_udid'].$adsId.$params['now'].$params['rand'];
        }
        return md5($zkey);
    }
}

/**
* 获取广告分类
*/
if(!function_exists('zk_ads_get_categories')){
	function zk_ads_get_categories(){
		$db = db_mysql_conn(ZK_MYSQL_TB_ADS_CATEGORY,true);
		$db ->select(array('id','title'));
		$query = $db ->get(ZK_MYSQL_TB_ADS_CATEGORY);
		$map = array();
		if(0 < $query->num_rows()){
			$categories = $query->result_array();
			foreach ($categories as $key => $value) {
				$map[$value['id']] = $value['title'];
			}
		}
		return $map;
	}
}

/**
* 获取多个用户的标签
*/
if(!function_exists('zk_ads_get_multi_user_tags')){
	function zk_ads_get_multi_user_tags($udidArr){
	    list($oRedis, $isRedisConnected) = zk_ads_redis('user_tag');
	    if(!$isRedisConnected || empty($udidArr)){
	    	return array();
	    }
	    foreach ($udidArr as $key => $udid) {
	        $keys[] = md5(ZK_ADS_CACHE_USER_DSP_TAG_STAT.$udid);
	    }
	    $userTags = $oRedis->mget($keys);
	    foreach ($userTags as $k => $value) {
	    	$udid = $udidArr[$k];
	    	$tags = empty($value)? array(): unserialize(zk_gzdecode($value));
	    	$tagsMap[$udid] = $tags;
	    }

	    return $tagsMap;
	}
}

/**
* 判断是否是垃圾点击量
*/
if(!function_exists('zk_ads_is_spam_click')){
	function zk_ads_is_spam_click($params, $ads){
		//ZAKER自己的流量不做判断，只对合作方流量做判断
		if(empty($params['f']) || $params['f'] == 'default'){
			return array(false, '');
		}

        if (!empty($ads['custom_outward_channel']) &&
            !empty($ads['custom_min_click_rate']) &&
            !empty($ads['custom_max_click_rate'])) {
            return array(false, '');
        }
        
		$outward_channels = zk_ads_config('outward_channels');  //外部导量渠道
		if(in_array($params['f'], $outward_channels)){
			return array(false, '');
		}

		//以下广告投放到中国电信时，不做判断
		$allowedAdIds = array('5a66a53bb09efe7e5c000001', '5ae28bc3b09efe982100000e');

		if($params['f'] == 'china_telecom' && in_array($ads['_id'],$allowedAdIds) ){
			return array(false, '');
		}
		
		//判断同一个IP一天内是否正常点击
		$isNormalClick = zk_ads_cache_third_party_click_count_normal($params, strval($ads['_id']));
		if(!$isNormalClick) {
			return array(true, 'ip_frequent_click');
		}

		list($cacheRedis, $isRedisConnected) = zk_ads_redis('cache');
		if($isRedisConnected == false){
			return array(false, ''); 
		}

		$params['now'] = $_GET['time'];
	    $params['ads_group'] = !empty($_GET['ads_group'])? $_GET['ads_group']: $ads['ads_group'];
	    $params['action'] = 'click';
	    $zkey = zk_ads_get_zkey($params);
	    $nowTime = time();
	    $clicks = intval($cacheRedis->get($zkey)) + 1;
	    $is_spam = false;

	    $userShowCount = zk_ads_cache_user_ads_show_count_get($params["_udid"], $ads["ad_group_id"]);

	    $spam_reason = '';
	    if(empty($_GET['time']) || !is_numeric($_GET['time'])){
	    	$is_spam = true;
	        $spam_reason = 'invalid_time';
	    }elseif($_GET['zkey'] != $zkey){ 	//判断zkey是否对得上
	    	$is_spam = true;
	        $spam_reason = 'invalid_zkey';
	    }elseif($clicks>=3){ 				   //对同一个请求的广告进行多次点击
	    	$is_spam = true;
	        $spam_reason = 'frequent_click';
	    }elseif($clicks==2){
	    	$is_spam = true;
	        $spam_reason = 'twice_click';
	    }elseif($ads['source'] != 'ad_partner' && $userShowCount == 0 ){  //本次广告点击之前没有曝光
	    	$is_spam = true;
	        $spam_reason = 'no_show_count';
	    }elseif($nowTime-$_GET['time'] > 900){ //请求广告之后延时15分钟才点击
	    	$is_spam = true;
	        $spam_reason = 'delay_15_minutes';
	    }elseif($nowTime-$_GET['time'] > 600){
	        $spam_reason = 'delay_10_minutes';
	    }elseif($nowTime-$_GET['time'] > 300){
	        $spam_reason = 'delay_5_minutes';
	    }
	   
	   	$cacheRedis->incrBy($zkey, 1); //点击数加1
	    $cacheRedis->setTimeout($zkey, 180);

	    return array($is_spam, $spam_reason);
	}
}

/**
* 判断是否是不正常的曝光量
*/
if(!function_exists('zk_ads_is_spam_show')){
	function zk_ads_is_spam_show($params, $ads){

		if($ads['partner_id'] == "unidesk"){
			return array(false, '');
		}

		$allowedAdIds = array('5a66a53bb09efe7e5c000001', '5ae28bc3b09efe982100000e');
		if($params['f'] == 'china_telecom' && in_array($ads['_id'],$allowedAdIds) ){
			return array(false, '');
		}

		//特殊品牌广告（宝马）对用户展示不超过4次
		$special_advertisers = zk_ads_config('special_brand_advertisers');
		if(in_array($ads['aid'], $special_advertisers)){
			$totalShowCount = zk_ads_cache_user_ads_show_count_get($params['_udid'], $ads['ad_group_id']);
			if($totalShowCount >= 4){
 				return array(true, 'udid_frequent_show');
 			}
		}

		//判断同一个IP一天内是否正常曝光
		$isNormalShow = zk_ads_cache_third_party_show_count_normal($params, strval($ads['_id']));
		if(!$isNormalShow) {
			return array(true, 'ip_frequent_show');
		}

		if($ads['source'] != 'ad_partner' && $ads['deliver_type'] == 2){
			return array(false, '');
		}

		list($cacheRedis, $isRedisConnected) = zk_ads_redis('cache');
		if($isRedisConnected == false){
			return array(false, ''); 
		}

		$params['now'] = $_GET['time'];
	    $params['ads_group'] = !empty($_GET['ads_group'])? $_GET['ads_group']: $ads['ads_group'];
	    $params['action'] = 'show';
	    $zkey = zk_ads_get_zkey($params);
	    $nowTime = time();
	    $showCount = intval($cacheRedis->get($zkey)) + 1;
	    $is_spam = false;

	    $spam_reason = '';
	    if(empty($_GET['time']) || !is_numeric($_GET['time'])){
	    	$is_spam = true;
	        $spam_reason = 'invalid_time';
	    }elseif($_GET['zkey'] != $zkey){ 	//判断zkey是否对得上
	    	$is_spam = true;
	        $spam_reason = 'invalid_zkey';
	    }elseif($showCount>=3){ 				   //同一请求的广告多次曝光
	    	$is_spam = true;
	        $spam_reason = 'frequent_show';
	    }elseif($showCount==2){
	        $spam_reason = 'twice_show';
	        $is_spam = true;
	    }elseif($nowTime-$_GET['time'] > 1200){   //请求广告之后延时20分钟才曝光
	        $spam_reason = 'delay_20_minutes';
	        $is_spam = true;
	    }elseif($nowTime-$_GET['time'] > 900){   //请求广告之后延时15分钟才曝光
	        $spam_reason = 'delay_15_minutes';
	    }elseif($nowTime-$_GET['time'] > 600){
	        $spam_reason = 'delay_10_minutes';
	    }elseif($nowTime-$_GET['time'] > 300){
	        $spam_reason = 'delay_5_minutes';
	    }
	   
	   	$cacheRedis->incrBy($zkey, 1); //曝光数加1
	    $cacheRedis->setTimeout($zkey, 300);

	    return array($is_spam, $spam_reason);
	}
}

/**
 * 广点通判断是否是不正常的曝光量
 */
if(!function_exists('partner_ads_is_spam_show')){
    function partner_ads_is_spam_show($params, $ads)
    {
        $allowedAdIds = array('5a66a53bb09efe7e5c000001', '5ae28bc3b09efe982100000e');
        if ($params['f'] == 'china_telecom' && in_array($ads['_id'], $allowedAdIds)) {
            return array(false, '');
        }

        //判断同一个IP一天内是否正常曝光
        $isNormalShow = zk_ads_cache_third_party_show_count_normal($params, strval($ads['_id']));
        if (!$isNormalShow) {
            return array(true, 'ip_frequent_show');
        }

        list($cacheRedis, $isRedisConnected) = zk_ads_redis('cache');
        if ($isRedisConnected == false) {
            return array(false, '');
        }

        $params['now'] = $_GET['now'];
        $params['ads_group'] = !empty($_GET['ads_group']) ? $_GET['ads_group'] : $ads['ads_group'];
        $params['action'] = 'show';
        $zkey = partner_ads_get_zkey($params);
        $nowTime = time();
        $zkeyShow = partner_ads_get_zkey($params, $ads['_id']);
        $showCount = intval($cacheRedis->get($zkeyShow)) + 1;
        $is_spam = false;

        $spam_reason = '';
        if (empty($_GET['now']) || !is_numeric($_GET['now'])) {
            $is_spam = true;
            $spam_reason = 'invalid_time';
        } elseif ($_GET['zkey'] != $zkey) {    //判断zkey是否对得上
            $is_spam = true;
            $spam_reason = 'invalid_zkey';
        } elseif ($showCount >= 3) {                   //同一请求的广告多次曝光
            $is_spam = true;
            $spam_reason = 'frequent_show';
        } elseif ($showCount == 2) {
            $spam_reason = 'twice_show';
            $is_spam = true;
        } elseif ($nowTime - $_GET['now'] > 1200) {   //请求广告之后延时20分钟才曝光
            $spam_reason = 'delay_20_minutes';
            $is_spam = true;
        } elseif ($nowTime - $_GET['now'] > 900) {   //请求广告之后延时15分钟才曝光
            $spam_reason = 'delay_15_minutes';
        } elseif ($nowTime - $_GET['now'] > 600) {
            $spam_reason = 'delay_10_minutes';
        } elseif ($nowTime - $_GET['now'] > 300) {
            $spam_reason = 'delay_5_minutes';
        }

        $cacheRedis->incrBy($zkeyShow, 1); //曝光数加1
        $cacheRedis->setTimeout($zkeyShow, 300);

        return array($is_spam, $spam_reason);
    }
}

/**
 * 广点通判断是否是垃圾点击量
 */
if(!function_exists('partner_ads_is_spam_click')){
    function partner_ads_is_spam_click($params, $ads){
        $outward_channels = zk_ads_config('outward_channels');  //外部导量渠道
        if(in_array($params['f'], $outward_channels)){
            return array(false, '');
        }

        //以下广告投放到中国电信时，不做判断
        $allowedAdIds = array('5a66a53bb09efe7e5c000001', '5ae28bc3b09efe982100000e');

        if($params['f'] == 'china_telecom' && in_array($ads['_id'],$allowedAdIds) ){
            return array(false, '');
        }

        //判断同一个IP一天内是否正常点击
        $isNormalClick = zk_ads_cache_third_party_click_count_normal($params, strval($ads['_id']));
        if(!$isNormalClick) {
            return array(true, 'ip_frequent_click');
        }

        list($cacheRedis, $isRedisConnected) = zk_ads_redis('cache');
        if($isRedisConnected == false){
            return array(false, '');
        }

        $params['now'] = $_GET['now'];
        $params['ads_group'] = $_GET['ads_group'];
        $params['action'] = 'click';
        $zkey = partner_ads_get_zkey($params);
        $nowTime = time();
        $zkeyClick = partner_ads_get_zkey($params, $ads['_id']);
        $clicks = intval($cacheRedis->get($zkeyClick)) + 1;
        $is_spam = false;

        $userShowCount = zk_ads_cache_user_ads_show_count_get($params["_udid"], $ads["_id"]);

        $spam_reason = '';
        if(empty($_GET['now']) || !is_numeric($_GET['now'])){
            $is_spam = true;
            $spam_reason = 'invalid_time';
        }elseif($_GET['zkey'] != $zkey){ 	//判断zkey是否对得上
            $is_spam = true;
            $spam_reason = 'invalid_zkey';
        }elseif($clicks>=3){ 				   //对同一个请求的广告进行多次点击
            $is_spam = true;
            $spam_reason = 'frequent_click';
        }elseif($clicks==2){
            $is_spam = true;
            $spam_reason = 'twice_click';
        }elseif($ads['source'] != 'ad_partner' && $userShowCount == 0 ){  //本次广告点击之前没有曝光
            $is_spam = true;
            $spam_reason = 'no_show_count';
        }elseif($nowTime-$_GET['now'] > 900){ //请求广告之后延时15分钟才点击
            $is_spam = true;
            $spam_reason = 'delay_15_minutes';
        }elseif($nowTime-$_GET['now'] > 600){
            $spam_reason = 'delay_10_minutes';
        }elseif($nowTime-$_GET['now'] > 300){
            $spam_reason = 'delay_5_minutes';
        }

        $cacheRedis->incrBy($zkeyClick, 1); //点击数加1
        $cacheRedis->setTimeout($zkeyClick, 180);

        return array($is_spam, $spam_reason);
    }
}

/**
* 将垃圾点击量写入队列，用于统计
*/
if(!function_exists('zk_ads_push_to_bad_click_queue')){
	function zk_ads_push_to_bad_click_queue($oReq, $ads){
        $arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
        $arrQueue = array(
            'ads_group' => $ads['ads_group'],
            'ads_id' => strval($ads['_id']),
            'event_type' => 'third_click',
            'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
            'device_id' => strval($oReq['device_id']) ,
            'udid' => $oReq['_udid'],
            'dtime' => $oReq['now'],
            'province' => $oReq['_province'],
            'city' => $oReq['_city'],
            'ip' => $oReq['ip'],
            'user_tag' => '',
            'user_category' => '' ,
            'block_pk' => '',
            'prize_weight' => floatval($ads['prize_weight']),
            'new_app_id' => '',
            'app_version' => '',
            'category_first' => $ads['category_first'] ? strval($ads['category_first']) : '',
            'category_second' => $ads['category_second'] ? strval($ads['category_second']) : '',
            'category_third' => $ads['category_third'] ? strval($ads['category_third']) : '',
            'deliver_type' => $ads['deliver_type'] ? strval($ads['deliver_type']) : '',
            'cp_app_id'=>'',
            'ad_group_id' => $ads['ad_group_id'] ? strval($ads['ad_group_id']) : '',
            'creative_id'=>$oReq['creative_id'],
            'spam_reason' => $oReq['spam_reason'],
        );
		if($ads['source'] == 'ad_partner'){
			$arrQueue['is_partner_ad'] = 1;
			$arrQueue['ad_source'] = $oReq['ad_source'];
			$arrQueue['position_id'] = $oReq['position_id'] ? $oReq['position_id'] : '';

        }

        zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_THIRD_BAD_STAT, $arrQueue);
	}
}


/**
* 将不正常的曝光量写入队列，用于统计
*/
if(!function_exists('zk_ads_push_to_bad_show_queue')){
	function zk_ads_push_to_bad_show_queue($oReq, $ads){

		$event_type = 'third_show';
        $arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
        $arrQueue = array(
            'ads_group' => $ads['ads_group'],
            'ads_id' => strval($ads['_id']),
            'event_type' => $event_type,
            'device_type' => $arrDeviceTypeNameToNum[$oReq['_appid']],
            'device_id' => strval($oReq['device_id']) ,
            'udid' => $oReq['_udid'],
            'dtime' => $oReq['now'],
            'province' => $oReq['_province'],
            'city' => $oReq['_city'],
            'ip' => $oReq['ip'],
            'user_tag' => '',
            'user_category' => '' ,
            'block_pk' => intval($oReq['app_id']),
            'prize_weight' => $ads['prize_weight'] ? floatval($ads['prize_weight']) : floatval(0),
            'new_app_id' => trim((string)$oReq['new_app_id']),
			'app_version' => trim((string)$oReq['_version']),
            'category_first' => $ads['category_first'] ? strval($ads['category_first']) : '',
            'category_second' => $ads['category_second'] ? strval($ads['category_second']) : '',
            'category_third' => $ads['category_third'] ? strval($ads['category_third']) : '',
            'deliver_type' => $ads['deliver_type'] ? strval($ads['deliver_type']) : '',
            'cp_app_id'=>'',
            'ad_group_id' => $ads['ad_group_id'] ? strval($ads['ad_group_id']) : '',
            'creative_id'=> $oReq['creative_id'],
            'spam_reason' => $oReq['spam_reason'],
			'ad_udid' => $oReq['ad_udid'],
        );
		if($ads['source'] == 'ad_partner'){
			$arrQueue['is_partner_ad'] = 1;
			$arrQueue['ad_source'] = $oReq['ad_source'];
			$arrQueue['position_id'] = $oReq['position_id'] ? $oReq['position_id'] : '';
		}

        zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_THIRD_BAD_STAT, $arrQueue);
	}
}

/**
* 为每个广告选择合适的创意
*/
if(!function_exists('zk_ads_select_creative')){
	function zk_ads_select_creative($adsArr){
		if(empty($adsArr)){
			return $adsArr;
		}
		$creativeKeys = array();
		$campaignIds = array();
		foreach ($adsArr as $key => $ads) {
			if(!in_array($ads['ad_group_id'], $campaignIds)){
				$creativeKeys[] = ZK_ADS_CACHE_CAMPAIGN_CREATIVE.$ads['ad_group_id'];
	        	$campaignIds[]= $ads['ad_group_id'];
			}
		}
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		if($isRedisConnected == false){
			return $adsArr; 
		}
		//获取每个广告计划点击率最高的广告创意
		$campaignCreatives = $oRedis->mGet($creativeKeys);	 
	    $campaignCreatives = array_combine($campaignIds, $campaignCreatives);
	    foreach ($adsArr as $key => $ads) {
	    	if($ads['deliver_type'] == 1){
	    		$targetAmount = $ads['target_clicks']; //总预算量
	    		$contrastiveAmount = 10000;            //和总预算对比的量
	    		$thresholdAmount = 500;                //是否随机出创意的临界值
	    		$finishedAmount = intval($ads['campaign_click_count']); //已经完成的量
	    	}else{
	    		$targetAmount = $ads['target_views'];
	    		$contrastiveAmount = 2000000;            
	    		$thresholdAmount = 100000;
	    		$finishedAmount = intval($ads['campaign_show_count']);
	    	}

	    	$randomCreative = true; //是否随机出创意
	    	if($targetAmount > $contrastiveAmount){
	    		$randomCreative = ($finishedAmount > $thresholdAmount) ? false:true;
	    		//如果已经完成的量大于临界值，则选择点击率最高的创意，否则随机出创意
	    	}else{
	    		$finishedRatio = $targetAmount ? $finishedAmount/$targetAmount : 0;
	    		$randomCreative = ($finishedRatio > 0.05) ? false:true;
	    		//如果预算完成度大于5%，则选择点击率最高的创意，否则随机出创意
	    	}
	        if(!$randomCreative){
	        	//不是随机出创意时，80%的机会给点击率高的创意，20%的机会随机出创意
	        	$random = rand(0, 9);
	        	if($random > 7){
	        		$randomCreative = true;
	        	}
	        }

            $creative=array();//这里需要初始化，否则有可能使用上一个循环的变量

	        if($randomCreative){    //随机选择创意
	        	if(!empty($ads['creatives'])){
	               	$creativeCount = count($ads['creatives']);
	               	$creatives = array_values($ads['creatives']);
	               	$winId = rand(0, $creativeCount-1);
	               	$creative = $creatives[$winId];
	               	$creative['id'] = $creative['_id'];
	            }
	        }else{    //使用点击率最高的创意
	            if(!empty($campaignCreatives[$ads['ad_group_id']])){
		    		$creative = json_decode($campaignCreatives[$ads['ad_group_id']], true);
		    	}else{
		    		$creatives = array_values($ads['creatives']);
		    		$creative = $creatives[0];
		    		$creative['id'] = $creative['_id'];
		    	}
	        }
	        if($creative){
	        	$adsArr[$key]['creativeid']= $creative['id'];
		    	if($creative['title']!=''){
		    	    $adsArr[$key]['ads_content'] = $creative['title'];
	    	    }
	    	    if(!empty($creative['ads_pic'])){
	    	      	$adsArr[$key]['ads_pic'] = $creative['ads_pic'];
	    	    }
	    	    if(!empty($creative['ads_short_pic'])){
	    	      	$adsArr[$key]['ads_short_pic']=$creative['ads_short_pic'];
	    	    }
	    	    if(!empty($creative['multi_pics'])){
	    	      	$adsArr[$key]['multi_pics']=$creative['multi_pics'];
	    	    }
	        }
	    }
	    return $adsArr;
	}
}



if(!function_exists('zk_ads_conversion_set_cookie')){
	/**
	 * 广告转化跟踪：记录cookie
	 */
	function zk_ads_conversion_set_cookie($params){
		$now = time();
		$udid = $params['_udid'];
		$ads_id = $params['ads_id'];
		$creative_id = $params['creative_id'];
		$con_key = zk_ads_conversion_get_key($udid,$ads_id,$now);

		$expire=time()+86400*365;
		setcookie("zk_ads_con[now]", $now, $expire);
		setcookie("zk_ads_con[udid]", $udid, $expire);
		setcookie("zk_ads_con[ads_id]", $ads_id, $expire);
		setcookie("zk_ads_con[creative_id]", $creative_id, $expire);
		setcookie("zk_ads_con[con_key]", $con_key, $expire);
	}
}

if(!function_exists('zk_ads_conversion_clear_cookie')){
	/**
	 * 广告转化跟踪：清除cookie
	 */
	function zk_ads_conversion_clear_cookie()
	{
		$expire=time()-3600;
		setcookie("zk_ads_con[now]", "", $expire);
		setcookie("zk_ads_con[udid]", "", $expire);
		setcookie("zk_ads_con[ads_id]", "", $expire);
		setcookie("zk_ads_con[creative_id]", "", $expire);
		setcookie("zk_ads_con[con_key]", "", $expire);
	}
}


if(!function_exists('zk_ads_check_conversion_cookie_info')){
	/**
	 * 广告转化跟踪：判断cookie信息是否有效
	 */
	function zk_ads_check_conversion_cookie_info($cookieInfo) {
		//参数不全
		if(
			empty($cookieInfo['ads_id']) || empty($cookieInfo['now']) || empty($cookieInfo['udid']) ||
			empty($cookieInfo['creative_id']) || empty($cookieInfo['con_key'])
		) {
			return false;
		}

		// key不合法
		if(zk_ads_conversion_get_key($cookieInfo['udid'], $cookieInfo['ads_id'], $cookieInfo['now']) != $cookieInfo['con_key'] ) {
			return false;
		}

		return true;
	}
}

if(!function_exists('zk_ads_conversion_get_key')){
	/**
	 * 广告转化跟踪：转化cookie的防伪码
	 * @param $udid
	 * @param $ads_id
	 * @param $now
	 * @return string
	 */
	function zk_ads_conversion_get_key($udid, $ads_id, $now){
		$con_key = 'zk_conversion'.$udid.$ads_id.$now;
		return md5($con_key);
	}
}

if(!function_exists('zk_ads_get_conversion_info')){
	/**
	 * 广告转化跟踪：获取转化事件信息
	 * @param $cid
	 */
	function zk_ads_get_conversion_info($cid) {
		// redis key
		$key = 'zk_conversion_'.$cid;

		// read from redis
		list($cacheRedis, $isRedisConnected) = zk_ads_redis('cache');
		if($isRedisConnected) {
			$arr = $cacheRedis->get($key);
			if($arr&&$arr!='null') {
				return json_decode($arr, true);
			}
		}

		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_CONVERSION_DEF,TRUE);

		$db->where(array('_id' => ZK_MongoDB::createID($cid)));

		$result = $db->getOne( ZK_MONGO_TB_ZK_ADS_CONVERSION_DEF );

		if($isRedisConnected){
			$re = $cacheRedis->set($key ,json_encode($result));
			$re = $cacheRedis->setTimeout($key, 60*5);
		}

		return $result;
	}
}


if(!function_exists('zk_ads_db_ads_conversion_stat_incr')){
	/**
	 * 广告转化跟踪：保存转化数据到mongo
	 * @param $cid 转化id
	 * @param $ad_group_id 广告计划id
	 * @param $clickIncr 总点击增加
	 * @param null $arrDailyClickIncr 当日点击增加
	 */
	function zk_ads_db_ads_conversion_stat_incr($cid,$ad_group_id,$clickIncr,$arrDailyClickIncr = null){
		if(empty($cid) || empty($ad_group_id) || empty($clickIncr)){
			return false;
		}

		$arrConversionStat = zk_ads_db_ads_conversion_stat_get($cid, $ad_group_id, null);
		if(empty($arrConversionStat['cid'])) {
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT);

			$insertArr = array(
				'cid' => strval($cid),
				'ad_group_id' => strval($ad_group_id),
				'click' => intval($clickIncr),
				'add_time' => time()
			);

			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$insertArr['daily_clicks'][$date] += $incr;
				}
			}

			$result = $db->insert( ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT, $insertArr );
		} else {
			$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT);

			$db->where(array('cid' => strval($cid)));
			$db->where(array('ad_group_id' => strval($ad_group_id)));

			$updateArr = array();
			if(intval($clickIncr) > 0){
				$updateArr['$inc']['click'] = intval($clickIncr);
			}
			if(is_array($arrDailyClickIncr) && count($arrDailyClickIncr) > 0){
				foreach ($arrDailyClickIncr as $date => $incr){
					$updateArr['$inc']["daily_clicks.{$date}"] = intval($incr);
				}
			}

			$result = $db->update( ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT, $updateArr );
		}

		return $result;
	}
}


if(!function_exists('zk_ads_db_ads_conversion_stat_get')){
	/**
	 * 广告转化跟踪：从MONGO中读取转化统计数
	 * @param $cid
	 * @param $ad_group_id
	 * @param bool $readonly
	 * @return array|mixed|object
	 */
	function zk_ads_db_ads_conversion_stat_get($cid, $ad_group_id, $readonly=true){
		$db = db_mongoDB_conn(ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT,$readonly);

		$db->where(array('cid' => strval($cid)));
		$db->where(array('ad_group_id' => strval($ad_group_id)));
		$db->limit(1);

		$result = $db->get( ZK_MONGO_TB_ZK_ADS_CONVERSION_STAT );

		if(is_array($result) && count($result) == 1){
			return $result[0];
		}else{
			return $result;
		}
	}
}

/**
 * 增加渠道的广告请求数
 * @param string $device_id 渠道标识
 * @param string $ads_group 广告位
 * @param int $nIcr   增加的次数
 */
if(!function_exists('zk_ads_cache_channel_request_count_incr')){
	function zk_ads_cache_channel_request_count_incr($device_id, $ads_group, $nIncr = 1, $date=''){
		if(empty($device_id) || empty($ads_group)){
			return false;
		}
		if(!zk_ads_is_valid_ads_group($ads_group)){
			return false;
		}
		if(!$date){
			$date = date('Y-m-d');
		}
		$expiredDate = date('Y-m-d', strtotime('+3 day'));
		$expiredTime = strtotime($expiredDate);
		$key = md5(ZK_ADS_CACHE_PARTNER_REQUEST_COUNT. $date);
		$field = $device_id.':'.$ads_group;
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(TRUE == $isRedisConnected){
			try {
				$ret = $oRedis->hincrBy($key, $field, $nIncr);
				$oRedis->expireAt($key, $expiredTime);
				return $ret;
			} catch (Exception $e) {
				
			}
		}
		return false;
	}
}

/**
 * 获取渠道的广告请求数
 * @param string $device_id 渠道标识
 * @param string $ads_group 广告位
 * @param string $date 日期，格式：yyyy-mm-dd
 */
if(!function_exists('zk_ads_cache_channel_request_count_get')){
	function zk_ads_cache_channel_request_count_get($device_id='', $ads_group='', $date=''){
		if(!$date){
			$date = date('Y-m-d', strtotime('-1 day'));
		}
		$key = md5(ZK_ADS_CACHE_PARTNER_REQUEST_COUNT. $date);
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		if(TRUE == $isRedisConnected){
			try {
				if($device_id && $ads_group){
					$field = $device_id.':'.$ads_group;
					return $oRedis->hGet($key, $field);
				}else{
					return $oRedis->hGetAll($key);
				}
			} catch (Exception $e) {

			}
		}
		return false;
	}
}

/**
 * 增加渠道的广告响应数（返回广告的次数）
 * @param string $device_id 渠道标识
 * @param string $ads_group 广告位
 * @param int $nIcr   增加的次数
 */
if(!function_exists('zk_ads_cache_channel_response_count_incr')){
	function zk_ads_cache_channel_response_count_incr($device_id, $ads_group, $nIncr = 1, $date=''){
		if(empty($device_id) || empty($ads_group)){
			return false;
		}
		if(!zk_ads_is_valid_ads_group($ads_group)){
			return false;
		}
		if(!$date){
			$date = date('Y-m-d');
		}
		$expiredDate = date('Y-m-d', strtotime('+3 day'));
		$expiredTime = strtotime($expiredDate);
		$key = md5(ZK_ADS_CACHE_PARTNER_RESPONSE_COUNT. $date);
		$field = $device_id.':'.$ads_group;
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(TRUE == $isRedisConnected){
			try {
				$ret = $oRedis->hincrBy($key, $field, $nIncr);
				$oRedis->expireAt($key, $expiredTime);
				return $ret;
			} catch (Exception $e) {
				
			}
		}
		return false;
	}
}

/**
 * 获取渠道的广告响应数（返回广告的次数）
 * @param string $device_id 渠道标识
 * @param string $ads_group 广告位
 * @param string $date 日期，格式：yyyy-mm-dd
 */
if(!function_exists('zk_ads_cache_channel_response_count_get')){
	function zk_ads_cache_channel_response_count_get($device_id='', $ads_group='', $date=''){
		if(!$date){
			$date = date('Y-m-d', strtotime('-1 day'));
		}
		$key = md5(ZK_ADS_CACHE_PARTNER_RESPONSE_COUNT. $date);
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		if(TRUE == $isRedisConnected){
			try {
				if($device_id && $ads_group){
					$field = $device_id.':'.$ads_group;
					return $oRedis->hGet($key, $field);
				}else{
					return $oRedis->hGetAll($key);
				}
			} catch (Exception $e) {
				
			}
		}
		return false;
	}
}

/**
* 判断广告位$ads_group的值是否有效，只能以字母、数字和下划线组成
*/
if(!function_exists('zk_ads_is_valid_ads_group')){
	function zk_ads_is_valid_ads_group($ads_group){
		return preg_match('/^[a-z0-9]+(_?[a-z0-9])*$/i', $ads_group);
	}
}

/**
* 按http类型改变url的http前缀
* @param $url 链接url
* @param $http_type http类型，1: 不加http前缀，2：https，3：http
*/
if(!function_exists('zk_ads_change_http_prefix')){
	function zk_ads_change_http_prefix($url, $http_type=0){
		if(empty($url)){
			return '';
		}
		if($http_type == 1){  //不加http前缀
			$httpPrefix = '';
		}elseif($http_type == 2){  //https
			$httpPrefix = 'https:';
		}else{
			$httpPrefix = 'http:';		//http
		}

		if(stripos($url, 'http:') === 0){  //以http开头的url
			$url = substr_replace($url, $httpPrefix, 0, 5);
		}elseif(stripos($url, 'https:') === 0){  //以https开头的url
			$url = substr_replace($url, $httpPrefix, 0, 6);
		}
		return $url;
	}
}

if(!function_exists('zk_ads_cache_user_product_show_count_incr')){
	/**
	 * 增加用户看过某（广告）产品的次数
	 * @param string $userID  用户ID
	 * @param string $advID   广告主ID
	 * @param string $product   产品名称
	 * @param int $nIncr
	 */
	function zk_ads_cache_user_product_show_count_incr($userID, $advID, $product, $nIncr = 1, $expired = null){
		if(!empty($userID) && !empty($advID) && !empty($product)){
			
			if(!$expired){
				$expired = ZK_ADS_USER_PRODUCT_SHOW_CACHE_EXPIRE;
			}
			list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
			if(TRUE == $isRedisConnected){
				try {
					$key = md5(ZK_ADS_CACHE_USER_PRODUCT_SHOW_COUNT.$userID.$advID.$product);
					$oRedis->incrBy($key, $nIncr);
					$oRedis->setTimeout($key, $expired);
				} catch (Exception $e) {
				
				}
			}
		}
	}
}

if(!function_exists('zk_ads_cache_user_product_show_count_get')){
	/**
	 * 获取用户看过某些（广告）产品的次数
	 * @param string $userID  用户ID
	 * @param array $advertiser_products 广告主对应的产品列表，例：
	 *  $advertiser_products = array(
	 *  	'广告主A的ID' => array('产品1','产品2'),
	 *      '广告主B的ID' => array('产品3','产品4'),
	 *  )
	 * @return array
	 */
	function zk_ads_cache_user_product_show_count_get($userID, $advertiser_products){
		if(empty($userID) || empty($advertiser_products)){
			return array();
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(FALSE == $isRedisConnected){
			return array();
		}

		$ret = array();
		if(is_array($advertiser_products)){
			$keys = $cacheKeys = array();
			foreach ($advertiser_products as $advID => $products){
				if(!is_array($products)){
					continue;
				}
				foreach ($products as $product) {
					array_push($keys, $advID.'_'.$product);
					array_push($cacheKeys, md5(ZK_ADS_CACHE_USER_PRODUCT_SHOW_COUNT.$userID.$advID.$product));
				}
			}
			try {
				$values = $oRedis->mget($cacheKeys);
				$ret = array_combine($keys, $values);
			} catch (Exception $e) {
				
			}
		
		}

		return $ret;
	}
}

/**
 * 获取广告主的代理商id
 * @param string $aid 广告主id
 * @return string 代理商id
 */
if(!function_exists('zk_ads_get_advertiser_parentid')) {
	function zk_ads_get_advertiser_parentid($aid) {
		$advertiserCacheKey=str_replace('{id}', '', ZK_DSP_CACHE_ADVERTISER_DATA);
		$advertiserKey=$advertiserCacheKey.$aid;

		list($ads_cache_redis, $ads_cache_redis_ok) = zk_ads_redis('ads_cache');
		if($ads_cache_redis_ok) {
			$advertiserInfo = $ads_cache_redis->get($advertiserKey);
			$advertiser=json_decode($advertiserInfo,true);

			if($advertiser['parentid']) {
				return $advertiser['parentid'];
			} else {
				return zk_ads_advertiser_parent_get($aid);
			}
		}

		return false;
	}
}

/**
 * 加载广告主对应的代理商
 */
if(!function_exists('zk_ads_advertiser_parent_def')) {
	function zk_ads_advertiser_parent_def() {
		//redis
		list($ads_cache_redis, $ads_cache_redis_ok) = zk_ads_redis('ads_cache');
		if(!$ads_cache_redis_ok) {
			return false;
		}

		$db = db_mongoDB_conn(ZK_MONGO_TB_DSP_ADVERTISER,true);

		$db->where(array('status' => 2));
		$db->where_ne('type', 1); //不是代理商
		$db->select(array('parentid'));
		$result = $db->get( ZK_MONGO_TB_DSP_ADVERTISER );

		if(!$result){
			return false;
		}

		$cacheKey = 'dsp_string_cache_advertiser_parent_';
		foreach($result as $value) {
			$value['_id'] = (string)$value['_id'];
			$ads_cache_redis->setex($cacheKey . $value['_id'],86400,json_encode($value));
		}
		return true;
	}
}

/**
 * 获取广告主对应的代理商
 */
if(!function_exists('zk_ads_advertiser_parent_get')) {
	function zk_ads_advertiser_parent_get($aid) {
		$advertiserCacheKey= 'dsp_string_cache_advertiser_parent_';
		$advertiserKey=$advertiserCacheKey.$aid;

		list($ads_cache_redis, $ads_cache_redis_ok) = zk_ads_redis('ads_cache');
		if($ads_cache_redis_ok) {
			$advertiserInfo = $ads_cache_redis->get($advertiserKey);
			$advertiser=json_decode($advertiserInfo,true);

			if($advertiser['parentid']) {
				return $advertiser['parentid'];
			}else{
				//拿不到redis数据的情况
				$db = db_mongoDB_conn(ZK_MONGO_TB_DSP_ADVERTISER,true);

				$db->where(array('_id' => ZK_MongoDB::createID($aid)));
				$db->select(array('parentid'));
				$result = $db->getOne( ZK_MONGO_TB_DSP_ADVERTISER );

				if(!is_array($result) || empty($result['parentid']) ){
					return false;
				}

				$result['_id'] = (string)$result['_id'];
				$ads_cache_redis->setex($advertiserKey,86400,json_encode($result));

				return $result['parentid'];
			}
		}

		return false;
	}
}

if(!function_exists('zk_ads_get_ads_info')){
	/**
	 * 获取多个广告信息
	 * 
	 * @param <array> $arrAdsIds 广告ID数组
	 */
	function zk_ads_get_ads_info($arrAdsIds, $oReq){
		//cache_local是cache的从库，先连接从库
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache_local');
		if(!$isRedisConnected){
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		}
		$adInfos = array();
		if(class_exists('Yac')){
	      	//------------用yac减少读取流量 ---------------------
	        $adKeys=array();
	        $yac = new Yac();
	        foreach ($arrAdsIds as $key => $value) {
	        	$oneAdsJson = $yac->get($value);
	            if(!$oneAdsJson){
	                $oneAdsJson=$oRedis->get(ZK_ADS_CACHE_SINGLE_ADS_DEF.$value);
	                if($oneAdsJson){
	                    $yac->set($value, $oneAdsJson, 300);
	                }
	            }
	            $adInfos[ZK_ADS_CACHE_SINGLE_ADS_DEF.$value] = $oneAdsJson;
	        }
		}
		elseif($isRedisConnected){
			//------------用mget减少读取次数---------------------
			$adKeys=array();
			foreach ($arrAdsIds as $key => $value) {
				$adKeys[]=ZK_ADS_CACHE_SINGLE_ADS_DEF.$value;
			}
			$adInfos=$oRedis->mget($adKeys);           			 
		}
		return $adInfos;
	}
}

/**
* 获取58同城的广告（只在特定的集成频道列表展示）
*/
if(!function_exists('zk_ads_get_58tongcheng_ads')){
	function zk_ads_get_58tongcheng_ads($oReq){
		if(empty($oReq['tc_aid'])){
			return array();
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(!$isRedisConnected){
			return FALSE;
		}
		$adsIds = $oRedis->sMembers(ZK_ADS_CACHE_58_ADS_SET);
		if(!$adsIds){
			return array();
		}

		$adsInfo = zk_ads_get_ads_info($adsIds);

		$arrAdsDetails = array();
		$unfitAds = array();
		foreach ($adsInfo as $key => $oneAds) {
			if(!$oneAds){
				$unfitAds['58_ads_not_exists'][] = $key;
			   	continue;
			}
			if(!is_array($oneAds)){
				$oneAds = json_decode($oneAds,true);
			}
			$adId = strval($oneAds['_id']);
			//过滤掉状态不正常的广告
			if($oneAds['stat']!=1){
				$unfitAds['58_ads_unnormal'][] = $adId;
	            continue;
			}
			if($oneAds['ads_group'] != $oReq['ads_group']){
				$unfitAds['58_ads_unmatch_ads_group'][] = $adId;
				continue;
			}
			if($oneAds['aid'] != $oReq['tc_aid']){
				$unfitAds['58_ads_unmatch_advertiser'][] = $adId;
				continue;
			}
			if(empty($oneAds['creatives'])){
				$unfitAds['58_ads_no_creatives'][] = $adId;
				continue;
			}
			if($oReq['ads_group'] == 'block_page' && $oneAds['ads_type'] != 1){
				$unfitAds['58_ads_unmatch_ads_type'][] = $adId;
				continue; //频道列表只出大图广告
			}
			$arrAdsDetails[] = $oneAds;
		}

		zk_ads_add_log($unfitAds, '58_unfit_ads');

		$arrAdsDetails = zk_ads_select_creative($arrAdsDetails);
		if($arrAdsDetails){
			shuffle($arrAdsDetails);
			return $arrAdsDetails[0];
		}else{
			return array();
		}

	}
}

if(!function_exists('zk_ads_set_special_brand_ads_users')){
	/**
	 * 存储特殊品牌（宝马）广告的目标用户到缓存
	 * @param int $date  日期，格式为 20180807
	 */
	function zk_ads_set_special_brand_ads_users($date){
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(FALSE == $isRedisConnected){
			return false;
		}

		$db = db_mysql_conn(ZK_MYSQL_TB_USER_READ_CATEGORY, true);
		$db->distinct(true);
		$db->select(array('udid'));
		$db->where('addday', $date);
		$db->where_in('new_app_id', array(1,3,5,10,15));  //取科技、财经、汽车和新闻用户
		//$db->limit(100);
		$query = $db ->get(ZK_MYSQL_TB_USER_READ_CATEGORY);

		$userNum = 0;
		if(0 < $query->num_rows()){
			$udidArr = $query->result_array();
			foreach ($udidArr as $item) {
				$udid = $item['udid'];
				$key = md5(ZK_ADS_CACHE_SPECIAL_BRAND_ADS_USER.$udid);
    			$ret = $ret = $oRedis->set($key, 1);
    			$oRedis->setTimeout($key, 86400*7);  //有效期7天
    			if($ret){
    				$userNum++;
    			}
			}
		}
		return $userNum;
	}
}

if(!function_exists('zk_ads_is_special_brand_ads_user')){
	/**
	 * 判断是否是特殊品牌（宝马）广告的目标用户
	 * @param string $udid  用户ID
	 */
	function zk_ads_is_special_brand_ads_user($udid){
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(FALSE == $isRedisConnected){
			return false;
		}
		try {
			$key = md5(ZK_ADS_CACHE_SPECIAL_BRAND_ADS_USER.$udid);
			$ret = $oRedis->get($key);
			return $ret ? true:false;
		} catch (Exception $e) {
			return false;
		}
	}
}

if(!function_exists('zk_ads_get_special_brand_ads_channels')){
	/**
	 * 获取特殊品牌广告可以投放的频道
	 * @param $campaignId 广告计划ID
	 */
	function zk_ads_get_special_brand_ads_channels($campaignId){
		$nowTime = time();
		$channels = array();
		if($campaignId == SPECIAL_BRAND_CAMPAIGN_1_ID){
			if($nowTime > strtotime('2018-08-16 10:00:00') && $nowTime < strtotime('2018-08-17 10:00:00')){
	 			$channels[] = 430000;   //视频tab
	 		}
	 		if($nowTime > strtotime('2018-08-22 10:00:00') && $nowTime < strtotime('2018-08-23 10:00:00')){
	 			$channels[] = 400000;   //热点tab
	 		}
	 		if($nowTime > strtotime('2018-08-23 10:00:00') && $nowTime < strtotime('2018-08-24 10:00:00')){
	 			$channels[] = 1251;   //豪车频道
	 		}
	 		if($nowTime > strtotime('2018-08-29 10:00:00') && $nowTime < strtotime('2018-08-30 10:00:00')){
	 			$channels[] = 11869;   //智能生活频道
	 		}
	 		if($nowTime > strtotime('2018-09-05 10:00:00') && $nowTime < strtotime('2018-09-06 10:00:00')){
	 			$channels[] = 10383;   //自驾游频道
	 		}
	 		if($nowTime > strtotime('2018-09-11 10:00:00') && $nowTime < strtotime('2018-09-12 10:00:00')){
	 			$channels[] = 11869;   //智能生活频道
	 		}
	 		if($nowTime > strtotime('2018-09-13 10:00:00') && $nowTime < strtotime('2018-09-14 10:00:00')){
	 			$channels[] = 7;   //汽车频道
	 		}
	 		if($nowTime > strtotime('2018-09-18 10:00:00') && $nowTime < strtotime('2018-09-19 10:00:00')){
	 			$channels[] = 4;   //财经频道
	 		}
	 		if($nowTime > strtotime('2018-09-21 10:00:00') && $nowTime < strtotime('2018-09-22 10:00:00')){
	 			$channels[] = 11869;   //智能生活频道
	 		}
	 	}elseif($campaignId == SPECIAL_BRAND_CAMPAIGN_2_ID){
 			if($nowTime > strtotime('2018-08-16 10:00:00') && $nowTime < strtotime('2018-08-17 10:00:00')){
	 			$channels[] = 7;   //汽车频道
	 		}
 		}

 		return $channels;
	}
}

if(!function_exists('zk_ads_is_special_brand_ads_channels')){
	/**
	 * 判断当前频道是否是广告定向投放的频道
	 * @param $app_id 频道ID
	 * @param $channels 广告定向投放的频道 格式 array( array('channel'=>4,'position'=>1) )
	 */
	function zk_ads_is_special_brand_ads_channels($app_id, $channels){
		if(!is_array($channels)){
			return false;
		}
		foreach ($channels as $value) {
			if($app_id == $value['channel']){
				return true;
			}
		}
		return false;
	}
}

/**
* 获取频道列表特定的CPM广告（只在特定频道特定位置展示）
*/
if(!function_exists('zk_ads_get_block_special_ads')){
	function zk_ads_get_block_special_ads($oReq){
		
		$whiteListUdids = zk_ads_config('special_brand_white_list');
		list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
		zk_ads_add_log($arrUserFavTypeIds, 'user_favour_type_ids');
		//判断用户是否有喜好分类
		if(!in_array($oReq['_udid'], $whiteListUdids) && empty($arrUserFavTypeIds)){
			zk_ads_add_log("user has no interests", 'no_ads_reason');
			return array();
		}
		$special_advertisers = zk_ads_config('special_brand_advertisers'); 
		//获取特定的广告
		$arrWheres = array(
			'aid' => array('$in'=>$special_advertisers),
			'ads_group' => 'block_page',
			'deliver_type' => 2,         //CPM广告
			'stat' => 1,
		);
		$arrAdsDef = zk_ads_get_def_data($arrWheres, array(), array('_id','ad_group_id','packageid'));
		if(empty($arrAdsDef)){
			zk_ads_add_log("no ads in db", 'no_ads_reason');
			return array();
		}

		$videoTabId = "430000";   //视频tab的ID
		$today = date("Y-m-d");
		foreach ($arrAdsDef as $value) {
			$adsIds[] = (string)$value['_id'];
			$campaignIds[] = $value['ad_group_id'];
			$campaignIds[] = $value['ad_group_id'] ."_".$today;

			$pkgId = (string)$value['packageid'];
			if(!in_array($pkgId, $campaignIds)){
				$campaignIds[] = $pkgId;
			}

			if($oReq['app_id'] == $videoTabId){
				$campaignIds[] = $value['ad_group_id'].'_'.$videoTabId;
			}
		}
		
		$showCounts = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $campaignIds);
		$adsInfo = zk_ads_get_ads_info($adsIds);

		$arrAdsDetails = array();
		$unfitAds = array();
		$finalAds = array();
		foreach ($adsInfo as $key => $oneAds) {
			if(!$oneAds){
				$unfitAds['ads_not_exists'][] = $key;
			   	continue;
			}
			if(!is_array($oneAds)){
				$oneAds = json_decode($oneAds,true);
			}
			$adId = strval($oneAds['_id']);
			$campId = strval($oneAds['ad_group_id']);
			$pkgId = strval($oneAds['packageid']);       //广告组ID

			$campaignShowCount = intval($showCounts[$campId]);
			$campaignShowCountToday = intval($showCounts[$campId.'_'.$today]);
			$packageShowCount = intval($showCounts[$pkgId]);
			//视频tab位置曝光次数
			$videoTabShowCount = intval($showCounts[$campId.'_'.$videoTabId]);

			//过滤掉状态不正常的广告
			if($oneAds['stat']!=1){
				$unfitAds['ads_unnormal'][] = $adId;
	            continue;
			}
			if(empty($oneAds['creatives'])){
				$unfitAds['ads_have_no_creatives'][] = $adId;
				continue;
			}
			if(empty($oneAds['channel_positions'])){
				$unfitAds['unset_channel_positions'][] = $adId;
				continue;
			}
			//判断是否是目标频道
			$isTargetChannel = zk_ads_is_special_brand_ads_channels($oReq['app_id'], $oneAds['channel_positions']);
			if(!$isTargetChannel){
				$unfitAds['unmatch_target_channel'][] = $adId;
				continue;
			}
			//过滤掉没有命中地域的
			if($oneAds['location'] && !in_array($oReq['_city'],$oneAds['location']) && !in_array($oReq['_province'], $oneAds['location'])){
				$unfitAds['unmatch_location'][] = $adId;
	            continue;
			}

			if(in_array($oReq['_udid'], $whiteListUdids)){  //白名单用户
				if($campaignShowCountToday >= 3){  //每天最多展示3次
					$unfitAds['ads_exceed_user_max_show_count'][] = $adId;
					continue;
				}
			}else{
				$isTargetUser = array_intersect($arrUserFavTypeIds, $oneAds['favour_category']) ? true: false;
				if(!$isTargetUser){  //不是目标用户
					$unfitAds['unmatch_target_user'][] = $adId;
					continue;
				}

				if($packageShowCount >= 3 || $campaignShowCount >= 3){  //最多展示3次
					$unfitAds['ads_exceed_user_max_show_count'][] = $adId;
					continue;
				}
				//视频tab位置每天最多展示一次
				if($oReq['app_id'] == $videoTabId && $videoTabShowCount >= 1){
					$unfitAds['ads_exceed_video_tab_show_count'][] = $adId;
					continue;
				}
			}

			$finalAds[] = $adId;
			$arrAdsDetails[] = $oneAds;
		}

		zk_ads_add_log($unfitAds, 'block_special_ads_unfit');
		if(empty($arrAdsDetails)){
			zk_ads_add_log("no ads after filter", 'no_ads_reason');
			return array();
		}
		$arrAdsDetails = zk_ads_select_creative($arrAdsDetails);
		zk_ads_add_log($finalAds, 'block_special_ads');

		return $arrAdsDetails;
	}
}

if(!function_exists('zk_ads_cache_ads_group_show_count_incr')){
	/**
	 * 增加广告位的曝光数
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr   增加的次数
	 */
	function zk_ads_cache_ads_group_show_count_incr($ads_group, $nIncr = 1){

		$key = 'ags_'.md5(ZK_ADS_CACHE_ADS_GROUP_SHOW_COUNT. $ads_group);
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');

		if(TRUE == $isRedisConnected){
			try {
				$expireTime = $oRedis->ttl($key);
				if($expireTime <= 0){
					$expireTime = 86400*30; //30天有效期
				}
				$oRedis->incrBy($key, $nIncr);
				$oRedis->setTimeout($key, $expireTime);
			} catch (Exception $e) {
				
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_group_click_count_incr')){
	/**
	 * 增加广告位的点击数
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr   增加的次数
	 */
	function zk_ads_cache_ads_group_click_count_incr($ads_group, $nIncr = 1){

		$key = 'agc_'.md5(ZK_ADS_CACHE_ADS_GROUP_CLICK_COUNT. $ads_group);
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');

		if(TRUE == $isRedisConnected){
			try {
				$expireTime = $oRedis->ttl($key);
				if($expireTime <= 0){
					$expireTime = 86400*30; //30天有效期
				}
				$oRedis->incrBy($key, $nIncr);
				$oRedis->setTimeout($key, $expireTime);
			} catch (Exception $e) {
				
			}
		}
	}
}

if(!function_exists('zk_ads_cache_ads_group_stats_get')){
	/**
	 * 获取广告位的点击数，曝光数和点击率
	 * @param string $ads_group 广告位标识
	 */
	function zk_ads_cache_ads_group_stats_get($ads_group){

		$clickKey = 'agc_'.md5(ZK_ADS_CACHE_ADS_GROUP_CLICK_COUNT. $ads_group);
		$showKey = 'ags_'.md5(ZK_ADS_CACHE_ADS_GROUP_SHOW_COUNT. $ads_group);
		$keys = array($clickKey, $showKey);
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');

		$clickCount = $showCount = $clickRatio = 0;

		if(TRUE == $isRedisConnected){
			try {
				$ret = $oRedis->mget($keys);
				$clickCount = intval($ret[0]);
				$showCount = intval($ret[1]);
				$clickRatio = $showCount ? round($clickCount/$showCount, 4): 0;
			} catch (Exception $e) {
				
			}
		}
		if($clickRatio > 0.1){
			$clickCount = $clickRatio = 0;
		}
		$stats = array('click_count'=>$clickCount, 'show_count'=>$showCount, 'click_ratio'=>$clickRatio);
		
		return $stats;
	}
}

if(!function_exists('zk_ads_cache_creative_ads_group_show_count_incr')){
	/**
	 * 增加广告创意在某个广告位的曝光数
	 * @param string $cid   广告创意ID
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr    增加的次数
	 */
	function zk_ads_cache_creative_ads_group_show_count_incr($cid, $ads_group, $nIncr = 1){
		if(empty($cid) || empty($ads_group)){
			return false;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(TRUE == $isRedisConnected){
			try {
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_ADS_GROUP_SHOW_COUNT.$cid.$ads_group, 'cgs_', 16);
				$oRedis->incrBy($cacheKey, $nIncr);
				$oRedis->setTimeout($cacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				return true;
			} catch (Exception $e) {

			}
		}
		return false;
	}
}

if(!function_exists('zk_ads_cache_creative_ads_group_show_count_get')){
	/**
	 * 获取多个广告创意在某个广告位的曝光数
	 * @param array $creativeIds   多个广告创意ID数组
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr    增加的次数
	 */
	function zk_ads_cache_creative_ads_group_show_count_get($creativeIds, $ads_group){
		if(empty($creativeIds) || empty($ads_group)){
			return array();
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(TRUE == $isRedisConnected){
			try {
				foreach ($creativeIds as $cid) {
					$cacheKeys[] = zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_ADS_GROUP_SHOW_COUNT.$cid.$ads_group, 'cgs_', 16);
				}
				$values = $oRedis->mget($cacheKeys);
				return array_combine($creativeIds, $values);
			} catch (Exception $e) {

			}
		}
		return array();
	}
}

if(!function_exists('zk_ads_cache_creative_ads_group_click_count_incr')){
	/**
	 * 增加广告创意在某个广告位的点击数
	 * @param string $cid   广告创意ID
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr    增加的次数
	 */
	function zk_ads_cache_creative_ads_group_click_count_incr($cid, $ads_group, $nIncr = 1){
		if(empty($cid) || empty($ads_group)){
			return false;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(TRUE == $isRedisConnected){
			try {
				$cacheKey = zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_ADS_GROUP_CLICK_COUNT.$cid.$ads_group, 'cgc_', 16);
				$oRedis->incrBy($cacheKey, $nIncr);
				$oRedis->setTimeout($cacheKey, ZK_ADS_ADS_CACHE_EXPIRE);
				return true;
			} catch (Exception $e) {

			}
		}
		return false;
	}
}

if(!function_exists('zk_ads_cache_creative_ads_group_click_count_get')){
	/**
	 * 获取多个广告创意在某个广告位的点击数
	 * @param array $creativeIds   多个广告创意ID数组
	 * @param string $ads_group 广告位标识
	 * @param int $nIncr    增加的次数
	 */
	function zk_ads_cache_creative_ads_group_click_count_get($creativeIds, $ads_group){
		if(empty($creativeIds) || empty($ads_group)){
			return array();
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('ads_cache');
		if(TRUE == $isRedisConnected){
			try {
				foreach ($creativeIds as $cid) {
					$cacheKeys[] = zk_ads_cache_get_key(ZK_ADS_CACHE_CREATIVE_ADS_GROUP_CLICK_COUNT.$cid.$ads_group, 'cgc_', 16);
				}
				$values = $oRedis->mget($cacheKeys);
				return array_combine($creativeIds, $values);
			} catch (Exception $e) {

			}
		}
		return array();
	}
}

/**
* 过滤频道列表广告
*/
if(!function_exists('zk_ads_filter_ads_for_block_page')){
	function zk_ads_filter_ads_for_block_page($oneAds, $oReq){
		$special_advertisers = zk_ads_config('special_brand_advertisers');  //特殊品牌广告主
		$filterOut = false;
		$filterReason = '';

		if(in_array($oneAds['aid'], $special_advertisers)){
 			return array(true, 'disable_special_brand_ads');
		}

		if($_REQUEST['need_app_integration'] == 1){ //竖排集成频道（如本地tab）
 			//CPC类型只需要图文和3图样式广告
 			$allowedTypes = array(2,15);
 			if($oneAds['deliver_type'] == 1 && !in_array($oneAds['ads_type'], $allowedTypes)){
 				$filterOut = true;
 				$filterReason = 'unmatch_ads_type';
 			}
 		}else{

 		}
 		//集成频道只需要图文广告
		if($oReq['integrated_channel'] == 1){
			if($oneAds['ads_type'] != 2){
				$filterOut = true;
 				$filterReason = 'unmatch_ads_type';
			}
		}
 		return array($filterOut, $filterReason);
	}
}

/**
* 过滤热点位置广告
*/
if(!function_exists('zk_ads_filter_ads_for_article_recommend')){
	function zk_ads_filter_ads_for_article_recommend($oneAds, $oReq){
		$special_advertisers = zk_ads_config('special_brand_advertisers');  //特殊品牌广告主		
		$nowTime = time();
		$whiteListUdids = zk_ads_config('special_brand_white_list');
		//特殊品牌广告
		if(in_array($oneAds['aid'], $special_advertisers)){
			$whiteListUdids = array("E5A8966A-727C-4ECB-A29F-E552A78BB83D", "867793021374345", "861795031829164");
			//是否设置了投放频道
			if(empty($oneAds['channel_positions'])){
				return array(true, 'unset_channel_positions');
			}
			//判断是否是目标频道
			$isTargetChannel = zk_ads_is_special_brand_ads_channels($oReq['app_id'], $oneAds['channel_positions']);
			if(!$isTargetChannel){
				return array(true, 'unmatch_target_channel');
			}

 			if(in_array($oReq['_udid'], $whiteListUdids)){
				$campaignShowCountToday = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $oneAds['ad_group_id']."_".date("Y-m-d"));
				if($campaignShowCountToday >= 3){
					return array(true, 'exceed_special_ads_max_show_count');
				}
			}else{
				list($arrUserFavTypeIds,$arrUserFavTypeWeight,$sUserGender) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
	 			$isTargetUser = array_intersect($arrUserFavTypeIds, $oneAds['favour_category']) ? true: false;
	 			//不符合特殊品牌广告的目标用户
				if(!$isTargetUser){
					return array(true, 'unmatch_special_ads_target_user');
				}

				$packageShowCount = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $oneAds['packageid']);
				$campaignShowCount = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $oneAds['ad_group_id']);
				if($packageShowCount >= 3 || $campaignShowCount >= 3){
					return array(true, 'exceed_special_ads_max_show_count');
				}
			}
		}

 		return array(false, '');
	}
}

/**
* 过滤WAP文章底部广告
*/
if(!function_exists('zk_ads_filter_ads_for_wap_bottom_banner')){
	function zk_ads_filter_ads_for_wap_bottom_banner($oneAds, $oReq){
		$filterOut = false;
		$filterReason = '';

		$webKeys = array("taobao.com", "tmall.com");
		$matchedKey = zk_match_keyword($webKeys, $oneAds['ads_link_url']);
		//排除落地页链接为淘宝或天猫的广告
		if($matchedKey){	
 			$filterOut = true;
 			$filterReason = 'exclude_taobao_ads';
 		}

 		return array($filterOut, $filterReason);
	}
}

/**
* 过滤WAP精彩推荐位置广告
*/
if(!function_exists('zk_ads_filter_ads_for_wap_jingcai')){
	function zk_ads_filter_ads_for_wap_jingcai($oneAds, $oReq){
		$filterOut = false;
		$filterReason = '';

		$webKeys = array("taobao.com", "tmall.com");
		$matchedKey = zk_match_keyword($webKeys, $oneAds['ads_link_url']);
		//排除落地页链接为淘宝或天猫的广告
		if($matchedKey){	
 			$filterOut = true;
 			$filterReason = 'exclude_taobao_ads';
 		}

 		return array($filterOut, $filterReason);
	}
}

/**
* 过滤WAP信息流位置广告
*/
if(!function_exists('zk_ads_filter_ads_for_wap_news_feed')){
	function zk_ads_filter_ads_for_wap_news_feed($oneAds, $oReq){
		$filterOut = false;
		$filterReason = '';
		$adsType = $_GET['ads_type'] ? explode(",", $_GET['ads_type']) : array();

		if( !empty($adsType) && !in_array($oneAds['ads_type'],$adsType) ){	
 			$filterOut = true;
 			$filterReason = 'unmatch_ads_type';
 		}

 		return array($filterOut, $filterReason);
	}
}

/**
* 获取广告样式
*/
if(!function_exists('zk_ads_get_ads_type')){
	function zk_ads_get_ads_type($type=''){
		$types = array(
            1 => 'ZAKER 大图(640x360)',
            2 => 'ZAKER 小图+文字',
            3 => 'ZAKER 纯文字',
            4 => '原生开屏640*960',
            5 => '大图(640x360)',
            6 => '小图(330x220)+文字',
			7 => '纯文字',
            8 => '汽车类媒体Banner(640x100)',
            9 => '新闻类媒体Banner(640x100)',
            10 => '魅族信息流 大图（984*420）+文字',
            11 => '大图(600*500)',
            12 => '新闻信息流(140*100)',
            13 => '图文信息流(150*150)',
            14 => 'ZAKER Banner(640*100)',
            15 => 'ZAKER 3图+文字',
        );
        return empty($type)? $types: $types[$type];
	}
}


/**
 * 判断该udid是否已经下载过apk_name的安卓apk下载广告
 */
if(!function_exists('zk_ads_check_apk_user_used')){
	function zk_ads_check_apk_user_used($apkName, $udid) {

		$apkSetKey = md5(ZK_ADS_CACHE_USER_APK_NAME_SET.$apkName);

		//redis
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');

		if(!$isRedisConnected){
			zk_ads_add_log("connect redis error: ".microtime(true), 'ERROR');
			return FALSE;
		}

		return $oRedis->sIsMember($apkSetKey, $udid);
	}
}


if(!function_exists('zk_ads_get_ads_from_partner_platform')){
	/**
	 * 从合作方平台获取广告
	 */
	function zk_ads_get_ads_from_partner_platform($oReq, $isRemainderFlow = true){

		load_class('dsp/partner_ads');
		$partnerObj =  new PartnerAds();
		$adsInfo = $partnerObj->fetch_ads($oReq, $isRemainderFlow);

		$nowTime = time();
        //记录广告信息
        if($adsInfo){
        	$adsInfo = partner_ads_common_filter($adsInfo, $oReq);
        	if(empty($adsInfo)){
        		return false;
        	}
        	foreach ($adsInfo as $oneAdInfo) {
        		//淘宝Tanx的广告太多，所以每5秒记录一次
        		if($oneAdInfo["partner_id"] == "tanx" && $nowTime%5 != 0 ){
        			continue;
        		}
        		//inMobi的广告太多，所以每5秒记录一次
        		if($oneAdInfo["partner_id"] == "inmobi" && $nowTime%5 != 0 ){
        			//continue;
        		}
        		$oneAdInfo['state'] = 'normal';
        		$oneAdInfo['status'] = 1;
        		zk_ads_queue_lpush(ZK_ADS_QUEUE_PARTNER_ADS_INFO, $oneAdInfo);
        	}
        	return zk_ads_rank_partner_ads($adsInfo);
        }

        return false;
	}
}


if(!function_exists('zk_ads_get_ads_from_partner_adx_go')){

    /**
     * 从adx-go 获取合作方广告
     * @param $oReq
     * @param bool $isRemainderFlow
     * @return array|bool|mixed|string
     */
    function zk_ads_get_ads_from_partner_adx_go($oReq, $isRemainderFlow = true) {
    	
    	//return array();

        $carrierMap = array('中国移动'=> 1, '中国联通'=> 2, '中国电信'=> 3);
        $params = array(
            'device' => $oReq['_appid'],
            'udid' => $oReq['_udid'],
            'appid' => $oReq['app_id'],
            'ip' => $oReq['ip'],
            'brand' => $oReq['phone_brand'],
            'model' => $oReq['_os_name'],
            'net_type' => $oReq['_net'],
            'app_version' => $oReq['_version'],
            'idfa' => $oReq['_idfa'] ? $oReq['_idfa'] : "",
            'imei' => $oReq['_imei'] ? $oReq['_imei'] : "",
            'user_agent' => $oReq['user_agent'],
            'use_default_ua' => $_GET['use_default_ua'] ? true : false,
            'bsize' => $oReq['_bsize'],
            'ads_group' => $oReq['ads_group'],
            'deal_id' => $oReq['deal_id'],
            'is_remainder_flow' => $isRemainderFlow,
            'show_log' => $oReq['show_log'] ? true : false,
        );

        if ($oReq['_appid'] == 'iphone') {
            $params['os'] = $oReq['_dev'];
        } else {
            $params['os'] = $oReq['_os'];
        }

        if (!empty($oReq['_uid'])) {
            $params['uid'] = strval($oReq['_uid']);
        }

        if (in_array($oReq['carrier'], array("中国移动", "中国联通", "中国电信"))) {
            $params['carrier'] = $carrierMap[$oReq['carrier']];
        } else {
            $params['carrier'] = 1;
        }

        $adUrls = array(
            "http://192.168.13.6/ad/partner",
            "http://192.168.13.7/ad/partner",
            "http://192.168.13.8/ad/partner",
        );
        $random = rand(0, count($adUrls)-1);
        $requestUrl = $adUrls[$random];

        $data = curl_post_json($requestUrl, json_encode($params), 450);

        if (empty($data)) {
            return array();
        } else {
            $data = json_decode($data, true);

            if ($data['code'] != 0) {
                return array();
            }

            if ($params['show_log']) {
                zk_ads_add_log($data,"GO_ADX_LOG");
            }

            if (empty($data['data']['ads_info']) || empty($data['data']['recommend_id']) || empty($data['data']['ads_score'])) {
                return array();
            } else {
                return array($data['data']['recommend_id'], $data['data']['ads_score'], $data['data']['ads_info']);
            }
        }
    }
}

if (!function_exists('zk_ads_get_ads_from_unidesk')) {
    /**
     * unidesk 平台获取广告
     * @param $oReq
     * @param $adGroup
     * @param string $partnerId
     * @param bool $isFmt 是否遍历格式化数据
     * @return array
     */
    function zk_ads_get_ads_from_unidesk($oReq, $adGroup, $partnerId = 'unidesk', $isFmt = true) {
        
        return array();  //暂时不请求广告

        load_class('dsp/ad_get_from_unidesk');
        $unidesk = new Ad_Get_From_Unidesk();

        $showCount = zk_ads_cache_ads_show_count_get($partnerId);
		if($showCount > 100000){
			zk_ads_add_log($showCount, 'unidesk_ads_exceed_max_show_count');
			return array();
		}

        // 封面 cover
        $unidesk->setAdGroup($adGroup);
        $oReq['ads_group'] = $adGroup;

        // 是否返回广告
        $unidesk->setReturnAds(true);
        // 设置广告样式
        $oReq['ad_type'] = $unidesk->getAdType();

        $dealId = zk_ads_get_partner_deal_id($partnerId);
        // 设置交易ID
        $unidesk->setDealId($dealId);

        $adsInfo = $unidesk->fetch_ads($oReq);

        $statDate = date('Y-m-d_H');

        load_class('dsp/partner_ads');
        $partnerAds = new PartnerAds();
        $partnerAds->partner_ads_cache_channel_request_count_incr($partnerId, $adGroup, 1, $statDate, $oReq['app_id'], $dealId);

        $ad_list = array();
        $cache = array();

        if(!empty($adsInfo)){
            if ($isFmt) {
                switch ($adGroup) {
                    case 'article_recommend':
                        $format = 'zk_ads_format_article_recommend';
                        break;
                    case 'block_page':
                        $format = 'zk_ads_format_article_block';
                        break;
                    default:
                        $format = '';
                        break;
                }

                foreach ($adsInfo as $oneAd) {
                    if (!empty($format)) {
                        if ($format ==  'zk_ads_format_article_recommend') {
                            $num = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $oneAd['_id'] . '_' . date('Y-m-d'));
                            if (!$num || $num < 3) {
                                $adInfo = $format($oneAd, $oReq);
                                $ad_list[] = $adInfo;
                                $cache[] = $oneAd;
                            }
                        } else {
                            $adInfo = $format($oneAd, $oReq);
                            $ad_list[] = $adInfo;
                            $cache[] = $oneAd;
                        }
                    }

                }
            } else {
                $ad_list = $adsInfo;
                $cache = $adsInfo;
            }

        }

        if (!empty($cache)) {
            zk_ads_rank_partner_ads($cache);
            $partnerAds->partner_ads_cache_channel_response_count_incr($partnerId, $adGroup, 1,$statDate, $oReq['app_id'], $dealId);
        }

        return $ad_list;
    }
}

/**
* 获取合作渠道配置信息
*/
if(!function_exists('zk_ads_get_partners_info')){
	function zk_ads_get_partners_info(){
	    $url = 'http://upload.myzaker.com/tools/index.php?model=bjdsp&action=get_s_device_id';
	    $result = file_get_contents($url);
	    $deviceMap = array();
	    if($result) {
	        $result_data = json_decode($result, true);
	        $device_arr = json_decode($result_data['data'],true);
	        if(is_array($device_arr)){
	            foreach ($device_arr as $value) {
	                $device_id = strtolower($value['device_id']);
	                $deviceMap[$device_id] = $value;
	            }
	        }
	    }
	    return $deviceMap;
	}
}


if(!function_exists('zk_ads_rank_partner_ads')){
	/**
	 * 合作方广告排序，并生成缓存
	*/
	function zk_ads_rank_partner_ads($ads){
        $baseScore = 10000;
        if(empty($ads)){
            return array(null, array(), array());
        }
        list($oRedis, $isRedisConnected) = zk_ads_redis('cache');

        $recommendId = $ads[0]['_id'];
        foreach ($ads as $key => $ad) {
            $k = intval($key);
            $score = $baseScore - $k*500;
            $id = strval($ad['_id']);
            $adsInfo[$id] = $ad;
            $adsScore[] = array('ads_id'=>$id, 'score'=>$score);

            if($isRedisConnected){
                $oRedis->set(ZK_ADS_CACHE_SINGLE_ADS_DEF.$id, json_encode($ad));
                $oRedis->setTimeout(ZK_ADS_CACHE_SINGLE_ADS_DEF.$id, 3300);
            }
        }

        return array($recommendId, $adsScore, $adsInfo);
   	}
}

if(!function_exists('partner_ads_format_ad_show_url')){
	/**
	 * 构造合作方广告曝光url
	 */
	function partner_ads_format_ad_show_url($ads, $oReq){

		$ads_group = $oReq['ads_group'] ? $oReq['ads_group'] : $ads['ads_group'];
		$ad_source = $ads['partner_id'];

		$deal_id = zk_ads_get_partner_deal_id($ad_source);

		$show_url = "http://".ZK_ADS_DOMAIN."partner_ggs_show.php?ads_id={$ads['_id']}&ads_group={$ads_group}&_udid={$oReq['_udid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&ad_source={$ad_source}&posId={$ads['posId']}&province_code={$oReq['_province']}&city_code={$oReq['_city']}&phone_brand={$oReq['phone_brand']}&f=".urlencode($oReq['f'])."&deal_id={$deal_id}";
		
		$oReq['ads_id'] = $ads['_id'];
		$oReq['action'] = 'show';
		$show_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);

		return $show_url;
	}
}

if(!function_exists('partner_ads_format_ad_click_url')){
	/**
	 * 构造合作方广告点击url
	 */
	function partner_ads_format_ad_click_url($ads, $oReq){

		$ads_group = $oReq['ads_group'] ? $oReq['ads_group'] : $ads['ads_group'];
		$ad_source = $ads['partner_id'];

        $deal_id = zk_ads_get_partner_deal_id($ad_source);

        $click_url = "http://".ZK_ADS_DOMAIN."partner_ggs_click.php?ads_id={$ads['_id']}&ads_group={$ads_group}&_udid={$oReq['_udid']}&_appid={$oReq['_appid']}&app_id={$oReq['app_id']}&ad_source={$ad_source}&posId={$ads['posId']}&province_code={$oReq['_province']}&city_code={$oReq['_city']}&phone_brand={$oReq['phone_brand']}&f=".urlencode($oReq['f'])."&deal_id={$deal_id}";

        if($oReq['ads_group'] == 'article_bottom_banner' || $oReq['ads_group'] == 'wap_bottom_banner'){
        	//文章底部需要跳转到广告落地页
        	$click_url .= "&ad_url=".rawurlencode($ads['ads_link_url']);
        }

        $oReq['ads_id'] = $ads['_id'];
		$oReq['action'] = 'click';
		$click_url .= "&time={$oReq['now']}&zkey=".zk_ads_get_zkey($oReq);

        return $click_url;
	}
}

if(!function_exists('partner_ads_get_info')){
	function partner_ads_get_info($id){
		if(!$id){
			return array();
		}
		$db = db_mongoDB_conn(ZK_MONGO_TB_PARTNER_ADS_INFO);
		$db->where(array('ads_id' => $id));
		$result = $db->getOne(ZK_MONGO_TB_PARTNER_ADS_INFO);

		return $result;
	}
}

if(!function_exists('partner_ads_exist_ad')){
	function partner_ads_exist_ad($id){

		$db = db_mongoDB_conn(ZK_MONGO_TB_PARTNER_ADS_ID);
		$db->where(array('ads_id' => $id));
		$result = $db->getOne(ZK_MONGO_TB_PARTNER_ADS_ID);

		return $result? true:false;
	}
}

/**
* 将合作方广告信息存储到MongoDb
*/
if(!function_exists('partner_ads_info_store_to_db')){
	function partner_ads_info_store_to_db($adInfo){
		
		if(empty($adInfo['ads_id'])){
			return false;
		}
		$adId = $adInfo['ads_id'];
		$table = ZK_MONGO_TB_PARTNER_ADS_INFO;
		$db = db_mongoDB_conn($table);
		$idTable = ZK_MONGO_TB_PARTNER_ADS_ID;
		$idDb = db_mongoDB_conn($idTable);

		$exist = partner_ads_exist_ad($adId);
		if(!$exist){
			$idArr = array('ads_id'=>$adId);
			$idDb->insert($idTable, $idArr);

			$adInfo['create_time'] = time();
			$result = $db->insert($table, $adInfo);
		}else{
			$result = true;
		}
		
		return $result;
	}
}

/**
* 将合作方广告信息移到汇总表
*/
if(!function_exists('partner_ads_info_move_to_db')){
	function partner_ads_info_move_to_db($startTime, $endTime){
		$latestTable = ZK_MONGO_TB_PARTNER_ADS_INFO;
		$gatherTable = ZK_MONGO_TB_PARTNER_ADS_INFO_GATHER;
		$latestDb = db_mongoDB_conn($latestTable);
		$gatherDb = db_mongoDB_conn($gatherTable);

		$query = array(
			'create_time' => array('$gte' => $startTime, '$lt' => $endTime),
		);	
		$dataList = $latestDb->select()->where($query)->get($latestTable);
		$adsCount = 0;
		//将某个时间段的记录移到广告汇总表
		if($dataList){
			$idList = array($dataList);
			$adsCount = count($dataList);
			foreach ($dataList as $key => $value) {
				unset($dataList[$key]['_id']);
			}
			$ret = $gatherDb->batch_insert($gatherTable, $dataList, '', TRUE);
		}
		unset($dataList);
		$deleteRet = false;
		//删除某个时间段的记录
		if($ret){
			$deleteRet = $latestDb->where($query)->delete_all($latestTable);
		}
		
		return array('result' => $deleteRet, 'ads_count'=>$adsCount );
	}
}

if(!function_exists('partner_ads_filter_word_stat_get')){
	function partner_ads_filter_word_stat_get($id){
		if(!$id){
			return array();
		}
		$table = ZK_MONGO_TB_PARTNER_ADS_FILTER_STAT;
		$db = db_mongoDB_conn($table);
		$db->where(array('ads_id' => $id));
		$result = $db->getOne($table);

		return $result;
	}
}

/**
* 增加根据广告关键词过滤的次数
*/
if(!function_exists('partner_ads_filter_word_stat_incr')){
	function partner_ads_filter_word_stat_incr($adInfo, $incrNum=1){
		
		if(empty($adInfo['ads_id']) || empty($adInfo['filter_by_word'])){
			return false;
		}
		$adId = $adInfo['ads_id'];
		$filterWord = strval($adInfo['filter_by_word']);

		$table = ZK_MONGO_TB_PARTNER_ADS_FILTER_STAT;
		$db = db_mongoDB_conn($table);

		$stat = partner_ads_filter_word_stat_get($adId);
		if(empty($stat)){
			$insertArr = array(
				'ads_id' => $adId,
				'filter_count' => array($filterWord => $incrNum),
				'create_time' => time()
			);
			$result = $db->insert($table, $insertArr);
		}else{
			$updateArr['$inc']["filter_count.{$filterWord}"] = intval($incrNum);
			$db->where(array('ads_id' => $adId));
			$result = $db->update($table, $updateArr);
		}
		
		return $result;
	}
}

/**
* 合作方广告过滤不合适的广告
*/
if(!function_exists('partner_ads_common_filter')){
	function partner_ads_common_filter($adsInfo, $oReq){
		if(empty($adsInfo)){
			return array();
		}
		$newAdsInfo = array();
		$keywords = partner_ads_get_filter_keywords();
		$appFilterWords = $keywords['app'];
		$contentFilterWords = $keywords['content'];

		list($oRedis, $isRedisConnected) = zk_ads_redis('partner_ads_cache');
		if($isRedisConnected){
			$forbiddenAdIds = $oRedis->sMembers('partner_ads_rejected_id_list');
		}

		//用户最近看过哪些广告主的广告
		$arrUserViewedAdvs = zk_ads_cache_get_user_latest_viewed_advertisers($oReq['_udid']);

		$unfitAds = array();
		foreach ($adsInfo as $k => $oneAdInfo) {
			$adId = $oneAdInfo['_id'];
			if(in_array($oneAdInfo["aid"], $arrUserViewedAdvs)){
				$unfitAds['has_viewed_advertisers'][] = $adId;
				continue;
			}
			//过滤掉用户当天看过5次以上的广告
			$showCount = zk_ads_cache_user_ads_show_count_get($oReq['_udid'], $adId);
			if($showCount >= 5){
				$unfitAds['exceed_today_user_max_shows'][] = $adId;
				continue;
			}

			//过滤掉被禁止的广告
			if(!empty($forbiddenAdIds) && in_array($adId, $forbiddenAdIds)){
				$unfitAds['forbidden_ads'][] = $adId;
				continue;
			}
			//根据应用名关键词过滤
			$filterWord1 = zk_match_keyword($appFilterWords, $oneAdInfo['app_name']);
			if($filterWord1){
				$oneAdInfo['state'] = 'invalid';
				$oneAdInfo['status'] = 1;
				$oneAdInfo['filter_by_word'] = $filterWord1;
        		zk_ads_queue_lpush(ZK_ADS_QUEUE_PARTNER_ADS_INFO, $oneAdInfo);

        		$unfitAds['filtered_by_app_keywords'][] = $adId;
				continue;
			}
			//根据广告内容关键词过滤
			$adsContent = $oneAdInfo['ads_content'].$oneAdInfo['ads_name'];
			$filterWord2 = zk_match_keyword($contentFilterWords, $adsContent);
			if($filterWord2){
				$oneAdInfo['state'] = 'invalid';
				$oneAdInfo['status'] = 1;
				$oneAdInfo['filter_by_word'] = $filterWord2;
        		zk_ads_queue_lpush(ZK_ADS_QUEUE_PARTNER_ADS_INFO, $oneAdInfo);

        		$unfitAds['filtered_by_content_keywords'][] = $adId;
				continue;
			}

			$newAdsInfo[] = $oneAdInfo;
		}
		zk_ads_add_log($unfitAds, 'PARTNER_ADS_FILTERED');
		
		return $newAdsInfo;
	}
}

/**
* 获取用于过滤合作方广告的关键词
*/
if(!function_exists('partner_ads_get_filter_keywords')){
	function partner_ads_get_filter_keywords(){
		$categories = array('app', 'content');
		foreach ($categories as $cate) {
			$cacheKeys[] = 'partner_ads_filter_keywords_for_'.$cate;
		}
        list($oRedis, $isRedisConnected) = zk_ads_redis('partner_ads_cache');
        if($isRedisConnected){
            $cacheData = $oRedis->mGet($cacheKeys);
            $result = array();
            foreach ($cacheData as $k=>$value) {
            	$key = $categories[$k];
            	$result[$key] = unserialize($value);
            }
            return $result;
        }
        return array();
	}
}

/**
* 获取当天对淘宝广告的请求量
*/
if(!function_exists('partner_ads_get_today_tanx_ad_request_count')){
	function partner_ads_get_today_tanx_ad_request_count(){
		$nowHour = intval(date('H'));
		$requestCount = 0;
		for($h = 0; $h<=$nowHour; $h++){
			$statDate = date('Y-m-d_H', strtotime("-{$h} hour"));
			$stats = zk_ads_cache_channel_request_count_get('', '', $statDate);
			$requestCount += intval($stats['tanx:article_recommend']) + intval($stats['tanx:article_bottom_banner']);
		}
		return $requestCount;
	}
}

if(!function_exists('zk_ads_push_to_ad_event_queue')){
	/**
	 * 将广告数据写入广告统计队列
	 */
	function zk_ads_push_to_ad_event_queue($ads, $oReq, $event_type='show'){
		//读取用户喜爱的分类,权重
		list($arrUserFavTypeIds, $arrUserFavTypeWeight) = zk_ads_get_user_fav_types($oReq['_udid'], $oReq['device_id']);
		$arrDeviceTypeNameToNum = zk_ads_config('device_type_name_to_num');
		$arrQueue = array(
			'event_type' 		=> $event_type,
		    'ads_group' 		=> $oReq['ads_group'] ? $oReq['ads_group'] : $ads['ads_group'],
		    'ads_id' 			=> $ads['_id'] ? strval($ads['_id']) : '',
		    'aid' 				=> $ads['aid'] ? strval($ads['aid']) : '',
		    'ad_group_id' 		=> $ads['ad_group_id'] ? strval($ads['ad_group_id']) : '',
		    'device_type' 		=> $arrDeviceTypeNameToNum[$oReq['_appid']],
		    'device_id' 		=> strval($oReq['device_id']),
		    'udid' 				=> strval($oReq['_udid']),
		    'uid' 				=> trim((string)$oReq['_uid']),
		    'dtime' 			=> intval($oReq['now']),
		    'province' 			=> strval($oReq['_province']),
		    'city' 				=> strval($oReq['_city']),
		    'ip' 				=> strval($oReq['ip']),
		    'user_agent' 		=> strval($oReq['user_agent']),
		    'user_tag' 			=> '',
		    'user_category' 	=> implode(" ", $arrUserFavTypeIds) ,
		    'block_pk' 			=> intval($oReq['app_id']) ,
		    'ads_type' 			=> $ads['ads_type'] ? intval($ads['ads_type']) : '',
			'prize_weight' 		=> floatval($ads['prize_weight']),
			'deliver_type' 		=> $ads['deliver_type'] ? strval($ads['deliver_type']) : '',
		    'cp_app_id'			=> strval($oReq['cp_app_id']),
		    'creative_id'		=> strval($oReq['creative_id']),
			'new_app_id' 		=> strval($oReq['new_app_id']),
			'app_version' 		=> strval($oReq['_version']),
			'category_first' 	=> $ads['category_first'] ? strval($ads['category_first']) : '',
			'category_second' 	=> $ads['category_second'] ? strval($ads['category_second']) : '',
			'category_third' 	=> $ads['category_third'] ? strval($ads['category_third']) : '',
		    'category' 			=> $ads['category'] ? strval($ads['category']) : '',
		    'spam_reason' 		=> strval($oReq['spam_reason']),
		    'device_brand' 		=> strval($oReq['phone_brand']),
		    'device_model' 		=> strval($oReq['_os_name']),
		    'ad_info' 			=> $ads,
			'ad_udid'			=> strval($oReq['ad_udid']),
			'platform' 			=> strval($oReq['platform']),
		);

		if(!empty($_GET['mkw'])){
    		$arrQueue['hit_keyword'] = 1;
    		$arrQueue['main_keyword'] = base64_decode($_GET['mkw']);
		}

		// 合作方新增字段
		if($event_type == 'partner_ad_show' || $event_type == 'partner_ad_click'){
			$arrQueue['is_partner_ad'] = 1;
			$arrQueue['ad_source'] = $oReq['ad_source'];
			$arrQueue['position_id'] = $oReq['position_id'] ? $oReq['position_id'] : '';
			$arrQueue['deal_id'] = $oReq['deal_id'] ? $oReq['deal_id'] : '';
            if ($oReq['partner_id'] == 'adnetqq') {
                $arrQueue['adnetqq_ad_title'] = $oReq['adnetqq_ad_title'];
                $arrQueue['adnetqq_ad_desc'] = $oReq['adnetqq_ad_desc'];
                $arrQueue['adnetqq_ad_style'] = $oReq['adnetqq_ad_style'];
            }
		}

		if($event_type == 'show' || $event_type == 'partner_ad_show'){
			//曝光数据队列
			zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_STAT, $arrQueue);
		}
		elseif($event_type == 'click' || $event_type == 'partner_ad_click'){
            //点击数据队列
            zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_CLICK_STAT, $arrQueue);
            //同步到佛山队列
            if($event_type == 'click'){
                zk_ads_queue_lpush('ads_queue_ads_action_click_stat_to_fs', $arrQueue);
            }
            if (!empty($ads['custom_outward_channel']) &&
                !empty($ads['custom_min_click_rate']) &&
                !empty($ads['custom_max_click_rate'])) {
            
                zk_ads_batch_incr_show($arrQueue, $ads['custom_min_click_rate'], $ads['custom_max_click_rate']);
            }
		}else{
			return false;
		}
		return true;
	}
}

/**
* 获取多个曝光监测url
*/
if(!function_exists('zk_ads_get_show_stat_urls')){

	function zk_ads_get_show_stat_urls($ads, $oReq){
		if($ads['source'] == 'ad_partner'){  //从合作方平台获取的广告
			$urls[] = partner_ads_format_ad_show_url($ads, $oReq);
		}else{
			$urls[] = zk_ads_format_ad_show_url($ads, $oReq);
		}
		if(is_array($ads['show_urls']) && !empty($ads['show_urls'])){
			foreach ($ads['show_urls'] as $url) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}

/**
* 获取多个点击监测url
*/
if(!function_exists('zk_ads_get_click_stat_urls')){
	function zk_ads_get_click_stat_urls($ads, $oReq){
		
		$urls = array();
		if($oReq['ads_group'] != 'article_bottom_banner' && $oReq['ads_group'] != 'wap_bottom_banner'){
			if($ads['source'] == 'ad_partner'){  //从合作方平台获取的广告
				$urls[] = partner_ads_format_ad_click_url($ads, $oReq);
			}else{
				$urls[] = zk_ads_format_ad_stat_url($ads, $oReq);
			}
		}else{
			if(is_app_inner_url($ads['ads_link_url'])){  //app内部链接
				$urls[] = zk_ads_format_ad_stat_url($ads, $oReq);
			}
		}

		if(is_array($ads['click_urls']) && !empty($ads['click_urls'])){
			foreach ($ads['click_urls'] as $url) {
				$urls[] = $url;
			}
		}
		
		return $urls;
	}
}

/**
* 在文章底部广告位 使用隐藏图片的方式发送曝光上报请求
*/
if(!function_exists('zk_ads_format_show_stat_img')){
	function zk_ads_format_show_stat_img($ads, $oReq){
		$statUrls = zk_ads_get_show_stat_urls($ads, $oReq);
		$statImgs = '';
		foreach ($statUrls as $url) {
			$statImgs .= '<img style="display:none; border:0px; width:0px; height:0px" src="'.$url.'"/>';
		}
		return $statImgs;
	}
}

/**
* 在文章底部广告位 使用隐藏图片的方式发送点击上报请求
*/
if(!function_exists('zk_ads_format_click_stat_img')){
	function zk_ads_format_click_stat_img($ads, $oReq){
		$statUrls = zk_ads_get_click_stat_urls($ads, $oReq);
		$statImgs = '';
		foreach ($statUrls as $url) {
			$statImgs .= 'var aImg = document.createElement("img"); aImg.src="'.$url.'"; ';
		}
		return $statImgs;
	}
}

/**
* 使用302跳转的方式到达广告落地页
*/
if(!function_exists('zk_ads_get_ad_target_url')){
	function zk_ads_get_ad_target_url($ads, $oReq){
		if($ads['source'] == 'ad_partner'){  //从合作方平台获取的广告
			$url = partner_ads_format_ad_click_url($ads, $oReq);
		}else{
			$url = zk_ads_format_ad_link_url($ads, $oReq);
		}
		return $url;
	}
}

/**
* 设置热点推荐里品牌广告的位置
*/
if(!function_exists('zk_ads_set_brand_ad_position')){
	function zk_ads_set_brand_ad_position(){
		$nowTime = time();
		$brandAdsPosition = array(
			'iphone' => 0,
			'androidphone' => 0,
		);

		$db = db_mongoDB_conn(ZK_MONGO_TB_ARTICLE_AD, TRUE);
		$fields = array('title', 'show_order', 'device');
		$query = array(
 			'stat' => 1,
 			'app_id' => 400000,  //热点推荐位置
 			'start_time' => array('$lte'=>$nowTime),
 			'end_time' => array('$gte'=>$nowTime),
		);
		$result = $db->select($fields)->where($query)->get( ZK_MONGO_TB_ARTICLE_AD );
		if($result){
			foreach ($result as $value) {
				if(!is_array($value['device'])){
					continue;
				}
				foreach ($value['device'] as $device) {
					if(!isset($brandAdsPosition[$device])){
						continue;
					}
					if($value['show_order'] > $brandAdsPosition[$device]){
						$brandAdsPosition[$device] = $value['show_order'];
					}
				}
			}
		}
		
		foreach ($brandAdsPosition as $device => $pos) {
			$cacheKey = 'hotspot_brand_ad_position_in_'.$device;
			$cacheData[$cacheKey] = $pos;
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if($isRedisConnected){
			$oRedis->mSet($cacheData);
		}

		return $cacheData;
	}
}

/**
* 获取热点推荐里品牌广告的位置
*/
if(!function_exists('zk_ads_get_brand_ad_position')){
	function zk_ads_get_brand_ad_position($device){
		$cacheKey = 'hotspot_brand_ad_position_in_'.$device;
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if(!$isRedisConnected){
			return 0;
		}
		$pos = $oRedis->get($cacheKey);
		return $pos ? intval($pos) : 0;
	}
}

/**
 * 获取热点推荐用户上一次的广告主id
 */
if(!function_exists('zk_ads_get_user_recommand_last_advertiser')) {
	function zk_ads_get_user_recommand_last_advertiser($udid){
		$cacheKey = 'recommand_ad_user_last_advertiser_'.$udid;
		list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
		if($isRedisConnected == true){
			$data = $oRedis->get($cacheKey);
			return $data;
		}
		return false;
	}
}

/**
 * 设置热点推荐用户上一次的广告主id
 */
if(!function_exists('zk_ads_set_user_recommand_last_advertiser')) {
	function zk_ads_set_user_recommand_last_advertiser($udid, $aid) {
		if(!empty($udid) && !empty($aid)) {
			$cacheKey = 'recommand_ad_user_last_advertiser_'.$udid;
			list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
			if($isRedisConnected == true){
				try {
					$data = $oRedis->set($cacheKey, $aid);
					$oRedis->setTimeout($cacheKey, 3600*24);//缓存一天
				} catch (Exception $e) {

				}
			}
		}
	}

}


/**
 * 从ggo平台请求广告
 */
if(!function_exists('zk_ads_request_ads_from_ggo_platform')) {
	function zk_ads_request_ads_from_ggo_platform($requestParams) {
		if(empty($requestParams["imp"])){
			return array();
		}

		$adUrls = array(
			"http://192.168.13.3/ad",
			"http://192.168.13.4/ad",
			"http://192.168.13.5/ad",
		);
		$random = rand(0, count($adUrls)-1);
		$requestUrl = $adUrls[$random];
		$requestUrl = "http://210.14.132.178:1081/ad";
		$response = curl_post_json($requestUrl, json_encode($requestParams), 200);
		if($response == false){
			return array();
		}
		$response = json_decode($response, true);
		if($requestParams["show_log"]){  //输出日志log
			return $response;
		}

		if($requestParams["partner_id"] == "xunfei_adx"){
			foreach ($response["seatbid"] as $k1 => &$seatbid) {
				if(empty($seatbid["bid"])){
					unset($response["seatbid"][$k1]);
					continue;
				}
				foreach ($seatbid["bid"] as &$bid) {
					if ($bid["banner_ad"]["landing"] == "") {
						unset($bid["banner_ad"]);
					}
					if ($bid["native_ad"]["landing"] == "") {
						unset($bid["native_ad"]);
					}
				}
			}
			if(empty($response["seatbid"])){
				return array();
			}
		}
	
		return $response;
	}
}


if(!function_exists('zk_ads_get_request_params_for_ggo_platfrom')){
	function zk_ads_get_request_params_for_ggo_platfrom($oReq){

		//获取运营商和网络类型
        $carrierMap=array('中国移动'=>'1', '中国联调'=>'2', '中国电信'=>'3');
        $operator_type = $oReq['carrier'];
        $carrier = empty($carrierMap[$operator_type])? '': $carrierMap[$operator_type];

		$styleKey = $_GET["ads_group"];
		$showLog = !empty($_GET["show_log"]) ? true:false;
		if($styleKey == "landscape" || $styleKey == "third_large"){
			$instl = 1;
		}elseif($styleKey == "news_feed" || $styleKey == "third_small"){
			$instl = 2;
		}elseif($styleKey == "large_banner"){
			$instl = 4;
		}else{
			$instl = 0;
		}

		$impArr[$styleKey] = array(
			'adsGroups' 	=> $oReq["multi_ads_group"],
			'mainAdsGroup' 	=> $oReq["ads_group"],
			'instl' 		=> $instl,
			'adsAmount' 	=> 1,
		);

		$params = array(
			'partner_id' 	=> $oReq["device_id"],
			'partner_type' 	=> $oReq["partner_type"] ? $oReq["partner_type"] : "partner_ssp",
			'device' 		=> $oReq["_appid"],
			'udid' 			=> (string)$oReq["_udid"],
			'appid' 		=> (string)$oReq["app_id"],  //应用ID
			'ip' 			=> $oReq["ip"],
			'carrier'		=> $carrier,
			'net_type' 		=> (string)$oReq["_net"],
			'brand'			=> (string)$oReq["phone_brand"],
			'model'			=> "",
			'imp'			=> $impArr,
			'show_log'		=> $showLog,
		);
	
		return $params;
	}	
}


/**
 * 判断是否是活跃用户
 */
if(!function_exists('zk_ads_is_active_user')) {
	function zk_ads_is_active_user($udid){
		$cacheKey = zk_ads_cache_get_key("dsp_active_user_".$udid, 'au_', 16);
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if($isRedisConnected == true){
			$data = $oRedis->get($cacheKey);
			return $data ? true: false;
		}
		return false;
	}
}

/**
 * 设置一批活跃用户udid到缓存
 */
if(!function_exists('zk_ads_set_active_users')) {
	function zk_ads_set_active_users($limit=2000) {

		$nowHour = intval(date('H'));
		if($nowHour < 5){
			return array('ret'=>false, 'msg'=> "no need to run before 5:00");
		}
		list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
		if(FALSE == $isRedisConnected){
			return array('ret'=>false, 'msg'=> "can't connect to Redis");
		}

		$today = date('Ymd');
		$expiredTime = 86400;
		$numKey = "dsp_active_user_num_last_".$today;
		$lastUserNum = $oRedis->get($numKey);
		if($lastUserNum !== false){
			$lastUserNum = intval($lastUserNum);
		}
		if( $lastUserNum === 0 ){
			return array('ret'=>false, 'msg'=> "has set cache of all users");
		}

		$offsetKey = "dsp_active_user_table_offset_".$today;
		$offset = intval($oRedis->get($offsetKey));

		//从Mysql数据库获取一批活跃用户
		$db = db_mysql_conn(ZK_MYSQL_TB_USER_READ_CLASSIFY, true);
		$db->select(array('id','udid'));
		$db->where('addday', $today);
		$db->where('type', 'pv');
		$db->where('grad', 1);
		$db->order_by('id', "desc");
		$db->limit($limit, $offset);

		$query = $db ->get(ZK_MYSQL_TB_USER_READ_CLASSIFY);

		$userNum = $query->num_rows();
		$oRedis->set($numKey, $userNum);
		$oRedis->setTimeout($numKey, $expiredTime);  //有效期1天

		if(0 < $userNum){
			$offset = $offset + $limit;
			$oRedis->set($offsetKey, $offset);
			$oRedis->setTimeout($offsetKey, $expiredTime);  //有效期1天

			$udidArr = $query->result_array();
			foreach ($udidArr as $item) {
				//存储活跃用户id到缓存
				$udid = $item['udid'];
				$cacheKey = zk_ads_cache_get_key("dsp_active_user_".$udid, 'au_', 16);
    			$oRedis->set($cacheKey, 1);
    			$oRedis->setTimeout($cacheKey, 86400*2);  //有效期2天
			}
		}
		return array('ret'=>true, 'msg'=> "user count: ".$userNum);
	}
}


/**
 * 记录没有广告返回的原因
 *
 * @param <string> $date 日期时间 格式：yyyy-mm-dd 或 yyyy-mm-dd_HH
 * @param <array> $oReq 广告请求参数
 * @param <string> $reason  没有广告返回的原因
 * @param <int> $incrNum  无广告次数 
 * @return boolean
 */
if(!function_exists('zk_ads_record_no_ads_reason')) {
    function zk_ads_record_no_ads_reason($date='', $oReq, $reason, $incrNum=1){
        
        if($oReq['device_id'] != "default"){  //只记录ZAKER客户端请求
        	return false;
        }
        $ads_group = $oReq['ads_group'];
        if(empty($ads_group) || empty($reason)){
            return false;
        }
        if(!$date){
            $date = date('Y-m-d');
        }
        $expiredDate = date('Y-m-d', strtotime('+3 day'));
        $expiredTime = strtotime($expiredDate);
        $key = "no_ads_reason_". $date . "_". $ads_group;
        $field = $reason;

        list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
        if(TRUE == $isRedisConnected){
            try {
                $ret = $oRedis->hincrBy($key, $field, $incrNum);
                $oRedis->expireAt($key, $expiredTime);
                return $ret;
            } catch (Exception $e) {

            }
        }
        return false;
    }
}


/**
 * 获取无效请求参数
 *
 * @param <string> $date 日期时间 格式：yyyy-mm-dd 或 yyyy-mm-dd_HH
 * @param <string> $partner_id 渠道标识
 * @return mixed
 */
if(!function_exists('zk_ads_get_no_ads_reason')) {
    function zk_ads_get_no_ads_reason($date='', $ads_group){
        if(!$date){
            $date = date('Y-m-d');
        }
        $key = "no_ads_reason_". $date . "_". $ads_group;
        list($oRedis, $isRedisConnected) = zk_ads_redis('user_cache_2');
        if(TRUE == $isRedisConnected){
            try {
                return $oRedis->hGetAll($key);
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}

/**
 * 生成url验证码
 *
 * @param <string> $url
 * @return string
 */
if(!function_exists('zk_ads_get_url_code')) {
    function zk_ads_get_url_code($url){
    	$secretKey = "ZK_ADS_URL_ENCR";
    	$code = md5($secretKey."_".$url);
    	return substr($code, 5, 10);
    }
}

/**
 * 获取广点通配置
 */
if (!function_exists('partner_ads_adnetqq')) {
    function partner_ads_adnetqq($oReq, $ads_group) {
        //广告权限判断
        if (zk_ads_check_ad_permission($oReq)) {
            load_class('dsp/ad_get_from_adnetqq');
            $obj = new Ad_Get_From_Adnetqq($oReq, $ads_group);
            return $obj->getAdnetqq();
        } else {
            return array();
        }
    }
}

/**
* 设置封面DSP广告的展示顺序
* @param <string> $id 广告ID
* @param <int> $is_duzhan 是否独占广告封面，1/0
* @param <int> $play_rank 展示顺序
* @param
* @return bool/array
*/
if(!function_exists('zk_ads_set_cover_ad_rank')){
    function zk_ads_set_cover_ad_rank($id, $is_duzhan, $play_rank){

        if(empty($id) || !is_numeric($is_duzhan) || !is_numeric($play_rank)){
            return false;
        }
        if($is_duzhan > 0){
        	$is_duzhan = 1;
        }

        $expiredTime = 30*86400;  //一个月缓存
        $key = "dsp_cover_". $id;

        list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
        if(!$isRedisConnected){
        	return false;
        }

        try {
            $ret['set_is_duzhan'] = $oRedis->hSet($key, "is_duzhan", $is_duzhan);
            $ret['set_play_rank'] = $oRedis->hSet($key, "play_rank", $play_rank);
            $oRedis->setTimeOut($key, $expiredTime);
            return $ret;
        } catch (Exception $e) {

        }
        return false;
    }
}

/**
* 获取封面DSP广告的展示顺序
* @param <string> $id 广告ID
* @param <int> $default_rank 默认展示顺序
* @param
* @return array
*/
if(!function_exists('zk_ads_get_cover_ad_rank')) {
    function zk_ads_get_cover_ad_rank($id, $default_rank=60){

    	$default = array('is_duzhan'=>0, 'play_rank'=>$default_rank, 'default'=>1);
        if(empty($id)){
            return $default;
        }

        $key = "dsp_cover_". $id;
        list($oRedis, $isRedisConnected) = zk_ads_redis('cache');
        if(!$isRedisConnected){
        	return $default;
        }
        try {
            $ret = $oRedis->hGetAll($key);
            return empty($ret)? $default: $ret;
        } catch (Exception $e) {

        }

        return $default;
    }
}


if (!function_exists('zk_ads_add_outward_log')) {
    /**
     * 添加导量 log
     * @param $adsGroupId
     * @param $oReq
     * @param $arrAds
     */
    function zk_ads_add_outward_log($adsGroupId, $oReq, $arrAds) {
        $mongo = db_mongoDB_conn(ZK_MONGO_TB_ZK_AD_OUTWARD_LOG);

        if ($mongo) {
            $query = $mongo->where(array(
                'campaign_id' => $adsGroupId,
                'ads_group' => $oReq['ads_group'],
                'outward_channel' => $oReq['f'],
                'flow_channel' => $oReq['flow_channel'],
                'created_at' => strtotime('today'),
            ))->select(array('_id', 'show_num', 'click_num'))->getOne(ZK_MONGO_TB_ZK_AD_OUTWARD_LOG);

            if ($query) {
                $update = array(
                    'output_percent' => $arrAds[0]['output_percent'],
                    'output_radio' => $arrAds[0]['output_radio'],
                    'special_output_partners_click' => $arrAds[0]['special_output_partners_click'],
                    'special_output_partners_show' => $arrAds[0]['special_output_partners_show'],
                    'updated_at' => time(),
                );

                if (!empty($arrAds[0]['show_url'])) {
                    $showNum = $query['show_num'] ? $query['show_num'] : 0;
                    $update['show_num'] = $showNum + 1;
                }

                if (!empty($arrAds[0]['click_url'])) {
                    $clickNum = $query['click_num'] ? $query['click_num'] : 0;
                    $update['click_num'] = $clickNum + 1;
                }

                $mongo->where(array('_id' =>new MongoId($query['_id'])))
                    ->set($update)->update(ZK_MONGO_TB_ZK_AD_OUTWARD_LOG);
            } else {
                $insert = array(
                    'campaign_id' => $adsGroupId,
                    'campaign_name' => $arrAds[0]['title'],
                    'ads_group' => $oReq['ads_group'],
                    'ads_name' => $arrAds[0]['name'],
                    'deliver_type' => $arrAds[0]['deliver_type'],
                    'outward_channel' => $oReq['f'],
                    'flow_channel' => $oReq['flow_channel'],
                    'show_num' => !empty($arrAds[0]['show_url']) ? 1 : 0,
                    'click_num' => !empty($arrAds[0]['click_url']) ? 1 : 0,
                    'target_clicks' => $arrAds[0]['target_clicks'],
                    'output_percent' => $arrAds[0]['output_percent'],
                    'output_radio' => $arrAds[0]['output_radio'],
                    'special_output_partners_click' => $arrAds[0]['special_output_partners_click'],
                    'special_output_partners_show' => $arrAds[0]['special_output_partners_show'],
                    'created_at' => strtotime('today'),
                    'updated_at' => strtotime('today'),
                );
                $mongo->insert(ZK_MONGO_TB_ZK_AD_OUTWARD_LOG, $insert);
            }
        }
    }
}


if (!function_exists('zk_ads_batch_inrc_show')) {

    /**
     * 批量增加曝光
     * @param $data
     * @param $min
     * @param $max
     */
    function zk_ads_batch_incr_show($data, $min, $max) {
        // 根据设定的概率计算需要增加的次数
        $num = zk_ads_cache_ads_click_count_get($data['ads_id']);
        if ($num) {
            $data['event_type'] = 'show';
            $maxNum = intval($num/$min);
            $minNum = intval($num/$max);
            $incrNum = rand($minNum, $maxNum);
            $data['incr_show_num'] = $incrNum;
            zk_ads_queue_lpush(ZK_ADS_QUEUE_ADS_ACTION_BATCH_INCR_SHOW, $data);
        }
    }
}