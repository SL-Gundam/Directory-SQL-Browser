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

$serverName = 'SL-SERVER\SQLEXPRESS';
$connectionOptions = array( 'Database' => 'DirListPro' );

/* Connect using Windows Authentication. */
$conn = sqlsrv_connect( $serverName, $connectionOptions );
if( $conn === FALSE )
{
	die( FormatErrors( sqlsrv_errors() ) );
}

$tsql = '
SELECT name, create_date, modify_date
FROM sys.tables';

$t_queryresult = sqlsrv_query( $conn, $tsql );

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
?>
	<tr>
<?php
		if ( $t_printed_header === FALSE )
		{
			foreach ( $t_row AS $t_key => $t_value )
			{
?>
		<td><?php echo $t_key ?></td>
<?php
			}
			$t_printed_header = TRUE;
?>
	</tr>
	<tr>
<?php
		}

		foreach ( $t_row AS $t_key => $t_value )
		{
?>
		<td><?php echo ( ( $t_key === 'name' ) ? '<a href="?table=' . $t_value . '">' : NULL ), ( ( is_object( $t_value ) ) ? $t_value->format( 'd-m-Y H:i:s' ) : $t_value ), ( ( $t_key === 'name' ) ? '</a>' : NULL ) ?></td>
<?php
		}
?>
	</tr>
<?php
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
