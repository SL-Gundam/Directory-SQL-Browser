<?php
$c_serverName = 'SL-SERVER\SQLEXPRESS';
$connectionOptions = array(
	'Database' => 'DirListPro',
	'CharacterSet' => 'UTF-8',
//	'UID' => '',
//	'PWD' => '',
);

header("Content-type: text/html; charset=utf-8;\n"); 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>SQL Directory Browser DirListPro</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;">
</head>
<body>
<?php
function FormatErrors( $errors )
{
	/* Display errors. */
	echo 'Error information: <br/>';

	foreach ( $errors as $error )
	{
		echo 'SQLSTATE: ' . $error[ 'SQLSTATE' ] . '<br/>
Code: ' . $error[ 'code' ] . '<br/>
Message: ' . $error[ 'message' ] . '<br/>
Query: <pre>' . $GLOBALS[ 't_sql' ] . '</pre>';
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

function escape_sql_query( $p_param )
{
	$t_param = $p_param;

	$t_param = str_replace( '\\', '\\\\', $t_param );
	$t_param = str_replace( '%', '\%', $t_param );
	$t_param = str_replace( '_', '\_', $t_param );
	$t_param = str_replace( '[', '\[', $t_param );

	return( $t_param );
}

function custom_strstr( $p_haystack, $p_needle, $p_before_needle = FALSE )
{
	$t_result = strstr( $p_haystack, $p_needle, $p_before_needle );

	if ( $t_result === FALSE )
	{
		$t_result = $p_haystack;
	}

	return( $t_result );
}

/* Connect using Windows Authentication. */
$conn = sqlsrv_connect( $c_serverName, $connectionOptions );
if( $conn === FALSE )
{
	die( FormatErrors( sqlsrv_errors() ) );
}

$t_sql_sort_option = ( ( !empty( $_GET[ 'sort' ] ) && !empty( $_GET[ 'sortorder' ] ) ) ? $_GET[ 'sort' ] . ' ' . $_GET[ 'sortorder' ] . ', ' : NULL );

if ( empty( $_GET[ 'table' ] ) )
{
	$t_sql = '
SELECT name, create_date, modify_date
FROM sys.tables
ORDER BY ' . $t_sql_sort_option . 'name asc';
}
else
{
	if ( empty( $_GET[ 'directory' ] ) )
	{
		$_GET[ 'directory' ] = '_:\\\\';
		$t_directory = $_GET[ 'directory' ];
	}
	else
	{
		$t_directory = escape_sql_query( $_GET[ 'directory' ] );
	}

	$t_sql = '
SELECT *
FROM ' . $_GET[ 'table' ] . '
WHERE ( Path IS NULL AND Name LIKE N\'' . $t_directory . '%\' ESCAPE \'\\\' ) OR
	Path LIKE N\'' . $t_directory . '\' ESCAPE \'\\\'
ORDER BY ' . $t_sql_sort_option . 'Path asc, Name asc';
}

$t_queryresult = sqlsrv_query( $conn, $t_sql );

if ( $t_queryresult === false )
{
	die( FormatErrors( sqlsrv_errors() ) );
}

if( sqlsrv_has_rows( $t_queryresult ) )
{
	$t_printed_header = FALSE;

	echo '<table border=1>';

	while( $t_row = sqlsrv_fetch_array( $t_queryresult, SQLSRV_FETCH_ASSOC ) )
	{
		if ( $t_printed_header === FALSE )
		{
			echo '<tr>';

			foreach ( $t_row AS $t_key => $t_value )
			{
				$t_sort_option = TRUE;
				if ( strcasecmp( $t_key, 'Path' ) === 0 || strcasecmp( $t_key, 'Name' ) === 0 )
				{
					$t_sort_option = FALSE;
				}

				echo '<th nowrap="nowrap">', ( ( $t_sort_option === TRUE ) ? '<a href="' . $_SERVER[ 'PHP_SELF' ] . '?' . custom_strstr( $_SERVER[ 'QUERY_STRING' ], '&sort=', TRUE ) . '&sort=' . urlencode( $t_key ) . '&sortorder=' . ( ( isset( $_GET[ 'sort' ], $_GET[ 'sortorder' ] ) && $_GET[ 'sort' ] === $t_key && $_GET[ 'sortorder' ] === 'desc' ) ? 'asc' : 'desc' ) . '">' : NULL ), $t_key, ( ( $t_sort_option === TRUE ) ? '</a>' : NULL ), '</th>';
			}
			$t_printed_header = TRUE;

			echo '</tr>';
			
			if ( isset( $_GET[ 'table' ], $_GET[ 'directory' ] ) )
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

			echo '<tr><td></td><td nowrap="nowrap">
		<a href="">.</a>
	</td></tr>
	<tr><td></td><td nowrap="nowrap">
		<a href="?table=', $t_table, '&directory=', urlencode( $t_directory ), '">..</a>
	</td></tr>';
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
//echo '<tr><td nowrap="nowrap">row hidden: ' . $t_row[ 'Name' ] . '</td></tr>';
				}
			}
		}
		
		if ( $t_print_row === TRUE )
		{
			echo '<tr>';

			foreach ( $t_row AS $t_key => $t_value )
			{
				$t_param = NULL;

				if ( strcasecmp( $t_key, 'name' ) === 0 && ( !isset( $t_row[ 'Path' ] ) ) )
				{
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

				echo '<td nowrap="nowrap">', ( ( isset( $t_param ) ) ? '<a href="' . $_SERVER[ 'PHP_SELF' ] . '?table=' . $t_param . '">' : NULL ), ( ( is_object( $t_value ) ) ? $t_value->format( 'd-m-Y H:i:s' ) : $t_value ), ( ( isset( $t_param ) ) ? '</a>' : NULL ), '</td>';
			}

			echo '</tr>';
		}
	}

	echo '</table>';
}
else
{
	echo 'No records.<pre>' . $t_sql . '</pre>';
}

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $t_queryresult );
sqlsrv_close( $conn );

?>
</body>
</html>
