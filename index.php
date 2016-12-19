<?php
	include_once( "./define.php" );

	$page_type	= isset( $_POST[ 'type' ] )	? $_POST[ 'type' ]	: "";
	$targte_asn	= isset( $_POST[ 'asn' ] )	? $_POST[ 'asn' ]	: "";

	$errorString	= "";
	$successString	= "";
	$allCSVAry		= array();
	$apiResultAry[ 'id' ] = $apiResultAry[ 'name' ] = $apiResultAry[ 'irr_as_set' ] = "";

	if( $page_type === "search" ) {
		$targte_asn	= intval( $targte_asn );
		if( $targte_asn <= 0 ) {
			$errorString	= "AS番号は数列で入力してください";
		} else {
			if( FLOW_CSV_FILE_PATH !== "" ) {
				$csvAry			= readCSV( FLOW_CSV_FILE_PATH );
				foreach( $csvAry as $csvLine ) {
					if( empty( $csvLine ) ) break;
					$as_number	= ( empty( $csvLine[ 4 ] ) )	? 0 : intval( $csvLine[ 4 ] );
					$bitrate	= ( empty( $csvLine[ 12 ] ) )	? 0 : intval( $csvLine[ 12 ] );
					if( ( $as_number > 0 ) && ( $bitrate > 0 ) )
						$allCSVAry[ $as_number ] = $csvLine;
				}
			}
		}

		$netAPIresult	= file_get_contents( "https://www.peeringdb.com/api/net?asn={$targte_asn}" );
		$netAPIresult	= empty( $netAPIresult ) ? array() : json_decode( $netAPIresult , true );

		$apiResultAry[ 'id' ]				= $netAPIresult[ 'data' ][ 0 ][ 'id' ];
		$apiResultAry[ 'name' ]				= $netAPIresult[ 'data' ][ 0 ][ 'name' ];
		$apiResultAry[ 'irr_as_set' ]		= $netAPIresult[ 'data' ][ 0 ][ 'irr_as_set' ];
		$apiResultAry[ 'policy_general' ]	= $netAPIresult[ 'data' ][ 0 ][ 'policy_general' ];

		$netAPIresult	= file_get_contents( "https://www.peeringdb.com/api/net/{$apiResultAry[ 'id' ]}" );
		$netAPIresult	= empty( $netAPIresult ) ? array() : json_decode( $netAPIresult , true );

		$irrASSetName	= $netAPIresult[ 'data' ][ 0 ][ 'irr_as_set' ];
		$ipv4Prefix		= empty( $netAPIresult[ 'data' ][ 0 ][ 'info_prefixes4' ] ) ? IPv4_PREFIX_NUMBER	: $netAPIresult[ 'data' ][ 0 ][ 'info_prefixes4' ];
		$ipv6Prefix		= empty( $netAPIresult[ 'data' ][ 0 ][ 'info_prefixes6' ] ) ? IPv6_PREFIX_NUMBER	: $netAPIresult[ 'data' ][ 0 ][ 'info_prefixes6' ];

		$netAPIresult	= empty( $netAPIresult ) ? array() : $netAPIresult[ 'data' ][ 0 ][ 'netixlan_set' ];
		$netIXLanAry	= array();

		$ipv4BGPConfigAry	= ( BGP_CONFIG_IPv4_PATH !== "" )	? file( BGP_CONFIG_IPv4_PATH , FILE_IGNORE_NEW_LINES )	: array();
		$ipv6BGPConfigAry	= ( BGP_CONFIG_IPv6_PATH !== "" )	? file( BGP_CONFIG_IPv6_PATH , FILE_IGNORE_NEW_LINES )	: array();

		$ipv4ConfigAry	= array();
		$ipv6ConfigAry	= array();

		foreach( $netAPIresult as $netIXLan ) {
			$ixLanID		= intval( $netIXLan[ 'ixlan_id' ] );
			$netIXLanAry[]	= $ixLanID;

			foreach( $ipv4BGPConfigAry as $line ) {
				$targetLine			= $line;
				$targetLine			= str_replace( "<%TARGET_IPv4%>"	, $netIXLan[ 'ipaddr4' ]	, $targetLine );
				$targetLine			= str_replace( "<%TARGET_ASN%>"		, $targte_asn				, $targetLine );
				$targetLine			= str_replace( "<%IPv4_PREFIX%>"	, $ipv4Prefix				, $targetLine );
				$ipv4ConfigAry[]	= $targetLine;
			}
			$ipv4ConfigAry[]		= "";

			foreach( $ipv6BGPConfigAry as $line ) {
				$targetLine			= $line;
				$targetLine			= str_replace( "<%TARGET_IPv6%>"	, $netIXLan[ 'ipaddr6' ]	, $targetLine );
				$targetLine			= str_replace( "<%TARGET_ASN%>"		, $targte_asn				, $targetLine );
				$targetLine			= str_replace( "<%IPv6_PREFIX%>"	, $ipv4Prefix				, $targetLine );
				$ipv6ConfigAry[]	= $targetLine;
			}
			$ipv6ConfigAry[]		= "";
		}

		if( empty( $allCSVAry[ $targte_asn ] ) && empty( $netIXLanAry ) )
			$errorString	= "検索対象のAS番号は存在しませんでした";
		else
			$successString	= "検索に成功しました";

		$bgpConfigAry	= ( BGP_CONFIG_BASE_PATH !== "" )	? file( BGP_CONFIG_BASE_PATH , FILE_IGNORE_NEW_LINES )	: array();

		if( BGP_CONFIG_IPv4_PATH === "" ) {
			$bgpConfigAry	= explode( "<brbrbr>" , str_replace( "<%IPv4_LIST%>" , ""												, implode( "<brbrbr>" , $bgpConfigAry ) ) );
		}
		else {
			$bgpConfigAry	= explode( "<brbrbr>" , str_replace( "<%IPv4_LIST%>" , implode( "\n" , array_values( $ipv4ConfigAry ) )	, implode( "<brbrbr>" , $bgpConfigAry ) ) );
		}

		if( empty( $ipv4ConfigAry ) )
			$bgpConfigAry	= explode( "<brbrbr>" , str_replace( "<%IPv6_LIST%>" , ""												, implode( "<brbrbr>" , $bgpConfigAry ) ) );
		else
			$bgpConfigAry	= explode( "<brbrbr>" , str_replace( "<%IPv6_LIST%>" , implode( "\n" , array_values( $ipv6ConfigAry ) )	, implode( "<brbrbr>" , $bgpConfigAry ) ) );

		$bgpConfigAry	= explode( "<brbrbr>" , str_replace( "<%AS_NUMBER%>" , $targte_asn											, implode( "<brbrbr>" , $bgpConfigAry ) ) );
	}
?>
<!DCOTYPE html>
<html lang="ja">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">

		<title>
			ASN Search
		</title>

		<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Droid+Sans:400,700" />
		<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />

		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

		<style type='text/css'>
			input[type=number]::-webkit-inner-spin-button,
			input[type=number]::-webkit-outer-spin-button {
				-webkit-appearance: none;
				margin: 0;
			}
			.table.table-bordered.table-bordered tbody th ,
			.table.table-bordered.table-bordered tbody td {
				vertical-align: middle;
			}

			body {
				padding-top: 30px;
			}
		</style>

		<script>
			<?php if( !empty( $bgpConfigAry ) ) { ?>
				var bgpConfig = <?php echo json_encode( $bgpConfigAry ); ?>.join( "\n" );
			<?php } ?>
		</script>
	</head>

	<body>
		<div class="container text-center">
			<article>
				<form class="form form-horizontal" method="post" action="<?php echo $_SERVER[ 'SCRIPT_NAME' ]; ?>">
					<?php if( !empty( $successString ) ) { ?>
						<article class="alert alert-success" role="alert">
							<p><?php echo $successString; ?></p>
						</article>
					<?php } else if( !empty( $errorString ) ) { ?>
						<article class="alert alert-danger" role="alert">
							<p><?php echo $errorString; ?></p>
						</article>
					<?php } ?>
					<fildset class="form-group">
						<label for="asn" class="col-sm-4 control-label">AS Number</label>
						<div class="col-sm-8">
							<input type="number" class="form-control" id="asn" name="asn" placeholder="AS番号" value="<?php echo $targte_asn; ?>" />
						</div>
					</fildset>
					<fildset class="form-group">
						<div class="col-sm-offset-4 col-sm-8 text-left">
							<button type="submit" class="btn btn-info" name="type" value="search">
								<span class="glyphicon glyphicon-search"></span>
								Search
							</button>
						</div>
					</fildset>
				</form>
				<?php if( empty( $errorString ) && !empty( $targte_asn ) ) { ?>
					<table class="table table-bordered table-bordered">
						<thead>
							<th>item</th>
							<th>status</th>
						</thead>
						<tbody>
							<tr>
								<th>AS</th>
								<td>
									<?php echo $targte_asn; ?>
								</td>
							</tr>
							<tr>
								<th>name</th>
								<td>
									<a href="https://www.peeringdb.com/asn/<?php echo $targte_asn ?>" target="_blank">
										<?php echo $apiResultAry[ 'name' ]; ?>
									</a>
								</td>
							</tr>
							<tr>
								<th>irr_as_set</th>
								<td><?php echo $apiResultAry[ 'irr_as_set' ]; ?></td>
							</tr>
							<tr>
								<th>policy_general</th>
								<td><?php echo $apiResultAry[ 'policy_general' ]; ?></td>
							</tr>
							<tr>
								<th>Connected IX</th>
								<td>
									<?php foreach( $targetIXButtonAry as $button ) { ?>
										<button type="button" data-ixlan-id="<?php echo $button[ 'ixLanID' ]; ?>" data-ixlan-prefix="<?php echo $button[ 'ixPrefix' ]; ?>" class="btn <?php echo in_array( intval( $button[ 'ixLanID' ] ) , $netIXLanAry , true ) ? 'btn-primary show-config' : 'disabled'; ?>"><?php echo $button[ 'displayName' ]; ?></button>
									<?php } ?>
								</td>
							</tr>
							<?php if( !empty( $allCSVAry ) ) { ?>
								<tr>
									<th>flow</th>
									<td><?php echo intval( $allCSVAry[ $targte_asn ][ 5 ] ); ?>（<?php echo $allCSVAry[ $targte_asn ][ 6 ]; ?>％）</td>
								</tr>
								<tr>
									<th>byte[MB]</th>
									<td><?php echo sprintf( "%.3f" , floatval( $allCSVAry[ $targte_asn ][ 9 ] / ( 1024 * 1024 ) ) ); ?>[MB]（<?php echo $allCSVAry[ $targte_asn ][ 10 ]; ?>％）</td>
								</tr>
								<tr>
									<th>bps[Mbps]</th>
									<td><?php echo sprintf( "%.3f" , floatval( $allCSVAry[ $targte_asn ][ 12 ] / ( 1024 * 1024 ) ) ); ?>[Mbps]</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>
			</article>
			<article id="config-text-modal" class="modal fade" tabindex="-1" role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<header class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<input type="text" id="as_name" class="form-control" />
						</header>
						<article class="modal-body">
							<textarea class="form-control" rows="40"></textarea>
						</article>
						<footer class="modal-footer">
						</footer>
					</div>
				</div>
			</article>
			<script>
				var baseConfigText	= "";
				var $modal			= $( "#config-text-modal" );
				$( "button.show-config" ).click( function() {
					var $this		= $( this );
					var targetIXID	= $this.attr( "data-ixlan-id" );
					var prefixIX	= $this.attr( "data-ixlan-prefix" );

					baseConfigText		= bgpConfig;
					baseConfigText		= baseConfigText.split( "<%IX-LAN-PREFIX%>" ).join( prefixIX );
					$modal.find( ".modal-body textarea" ).val( baseConfigText );
					$modal.modal( 'show' );

					$modal.find( "#as_name" ).val( "<?php echo $irrASSetName; ?>" ).change();
				});
				$modal.on( 'change' , "#as_name" , function() {
					var $this = $( this );
					$modal.find( ".modal-body textarea" ).val( baseConfigText.split( "<%AS-NAME%>" ).join( $this.val() ) );
				});

			</script>
		</div>
	</body>
</html>
<?php
	function readCSV( $targetFilePath ) {
		$records	= array();
		$file		= new SplFileObject( $targetFilePath );
		$file->setFlags( SplFileObject::READ_CSV );
		foreach( $file as $line ) {
			if( !is_null( $line[ 0 ] ) ) {
				$records[] = $line;
			}
		}
		return $records;
	}
?>
