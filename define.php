<?php
	// flow系のCSVファイルの絶対パス
	define( "FLOW_CSV_FILE_PATH"	, "" );

	// BGPコンフィグにおけるデフォルトのPrefixの値
	define( "IPv4_MAXIMUM_PREFIX_NUMBER"	, 1000 );
	define( "IPv6_MAXIMUM_PREFIX_NUMBER"	, 50 );

	// BGPコンフィグにおける設定情報
	define( "BGP_CONFIG_IPv4_PATH"	, "ipv4-bgp_config" );
	define( "BGP_CONFIG_IPv6_PATH"	, "ipv6-bgp_config" );

	// BGPコンフィグにおけるヘッダーテキスト
	define( "BGP_CONFIG_BASE_PATH"	, "bgp_config_base" );

	// 接続IXを記載する。ixPrefixはコンフィグで利用する際の名称
	$targetIXButtonAry	= array(
		array( 'ixLanID' => '95'	, 'displayName' => "JPNAP Tokyo1"		, 'ixPrefix' => 'JPNAP' ) ,
		array( 'ixLanID' => '30'	, 'displayName' => "JPIX Tokyo"			, 'ixPrefix' => 'JPIX' ) ,
		array( 'ixLanID' => '126'	, 'displayName' => "BBIX Tokyo"			, 'ixPrefix' => 'BBIX' ) ,
		array( 'ixLanID' => '1324'	, 'displayName' => "ASIA SmartIX"		, 'ixPrefix' => 'BBIX' ) ,
		array( 'ixLanID' => '167'	, 'displayName' => "EQUINIX Tokyo"		, 'ixPrefix' => 'EQUINIX' )
	);



	$_ = function( $s ) {
		return $s;
	};
