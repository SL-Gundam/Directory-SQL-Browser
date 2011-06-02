<?php
function FormatErrors( $errors )
{
	/* Display errors. */
	echo 'Error information: <br/>';

	foreach ( $errors as $error )
	{
		echo 'SQLSTATE: ' . $error[ 'SQLSTATE' ] . '<br/>
Code: ' . $error[ 'code' ] . '<br/>
Message: ' . $error[ 'message' ] . '<br/>';
	}
}

// this function formats the bytes so that they are easily readable.
function formatbytes( $bytes )
{
	$units = array( ' bytes', ' KiB', ' MiB', ' GiB', ' TiB' );
	for ( $i=0; $bytes > 1024; $i++ )
	{
		$bytes /= 1024;
	}
	return( round( $bytes, 2 ) . $units[ $i ] );
}

$serverName = 'SL-SERVER\SQLEXPRESS';
$connectionOptions = array( 'Database' => 'DirListPro' );

/* Connect using Windows Authentication. */
$conn = sqlsrv_connect( $serverName, $connectionOptions );
if( $conn === FALSE )
{
	die( FormatErrors( sqlsrv_errors() ) );
}

if ( empty( $_GET[ 'table' ] ) )
{
	$t_sql = '
SELECT name, create_date, modify_date
FROM sys.tables
ORDER BY name';
}
else
{
	if ( empty( $_GET[ 'directory' ] ) )
	{
		$_GET[ 'directory' ] = '_:\\';
	}

	$t_sql = '
SELECT Path, Name, Extension, Count, VolumeLabel, Size
FROM ' . $_GET[ 'table' ] . '
WHERE ( Path IS NULL AND Name LIKE "' . $_GET[ 'directory' ] . '%" ) OR
  Path LIKE "' . $_GET[ 'directory' ] . '"
ORDER BY Path, Name';
}

$t_sql = str_replace( '"', '\'', $t_sql );

$t_queryresult = sqlsrv_query( $conn, $t_sql );

if ( $t_queryresult === false )
{
	die( FormatErrors( sqlsrv_errors() ) );
}

if( sqlsrv_has_rows( $t_queryresult ) )
{
	$t_printed_header = FALSE;
?>
<table border=1>
<?php
	while( $t_row = sqlsrv_fetch_array( $t_queryresult, SQLSRV_FETCH_ASSOC ) )
	{
		if ( $t_printed_header === FALSE )
		{
?>
	<tr>
<?php
			foreach ( $t_row AS $t_key => $t_value )
			{
?>
		<td><?php echo $t_key ?></td>
<?php
			}
			$t_printed_header = TRUE;
?>
	</tr>
<?php
			if ( isset( $_GET[ 'table' ] ) )
			{
				$t_pos_limitcheck = strripos( $_GET[ 'directory' ], '\\', -2 );
				if ( $t_pos_limitcheck === FALSE )
				{
					$t_directory = NULL;
					$t_table = NULL;
				}
				else
				{
					$t_directory = substr( $_GET[ 'directory' ], 0, $t_pos_limitcheck + 1 );
					$t_table = $_GET[ 'table' ];
				}
				
//var_dump($t_pos_limitcheck, $_GET[ 'directory' ], $t_directory);
?>
	<tr><td></td><td>
		<a href="?table=<?php echo $t_table, '&directory=', urlencode( $t_directory ) ?>">..</a>
	</td></tr>
<?php
			}
		}

		$t_print_row = TRUE;
		
		if ( isset( $_GET[ 'directory' ], $t_row[ 'Name' ] ) && strlen( $_GET[ 'directory' ] ) < strlen( $t_row[ 'Name' ] ) )
		{
			$t_pos_limitcheck = stripos( $t_row[ 'Name' ], '\\', strlen( $_GET[ 'directory' ] ) );
			if ( $t_pos_limitcheck !== FALSE )
			{
//var_dump($t_pos_limitcheck, $t_row, $_GET[ 'directory' ] );
				$t_pos_limitcheck++;
				if ( array_key_exists( 'Path', $t_row ) && $t_row[ 'Path' ] === NULL && $t_pos_limitcheck !== strlen( $t_row[ 'Name' ] ) )
				{
//var_dump($t_pos_limitcheck, $t_row, $_GET[ 'directory' ] );
//exit;
					$t_print_row = FALSE;
//					echo '<tr><td>row hidden: ' . $t_row[ 'Name' ] . '</td></tr>';
				}
			}
		}
		
		if ( $t_print_row === TRUE )
		{
?>
	<tr>
<?php
			foreach ( $t_row AS $t_key => $t_value )
			{
				$t_param = NULL;

				if ( strcasecmp( $t_key, 'name' ) === 0 && ( !isset( $t_row[ 'Path' ] ) ) )
				{
					if ( isset( $_GET[ 'directory' ] ) )
					{
						$t_print_row = FALSE;
					}

					$t_param = urlencode( $t_value );
					if ( !empty( $_GET[ 'table' ] ) )
					{
						$t_param = urlencode( $_GET[ 'table' ] ) . '&directory=' . $t_param;
					}
				}

				if ( strcasecmp( $t_key, 'Size' ) === 0 )
				{
					$t_value = formatbytes( $t_value );
				}

?>
		<td><?php echo ( ( isset( $t_param ) ) ? '<a href="?table=' . $t_param . '">' : NULL ), ( ( is_object( $t_value ) ) ? $t_value->format( 'd-m-Y H:i:s' ) : $t_value ), ( ( isset( $t_param ) ) ? '</a>' : NULL ) ?></td>
<?php
			}
?>
</tr>
<?php
		}
	}
?>
</table>
<?php
}
else
{
	echo 'No records.';
}

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $t_queryresult );
sqlsrv_close( $conn );

?>
