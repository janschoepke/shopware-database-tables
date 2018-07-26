<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

include 'mysqlData.php';

function getSQLTables($database){
    global $mysqli;
    $mysqli->select_db($database);

    $result = $mysqli->query("SELECT TABLE_NAME AS tableName FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $database . "';");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTableRows($table) {
    global $mysqli;
    $result = $mysqli->query("DESCRIBE " . $table . ";");
    return $result->fetch_all(MYSQLI_NUM);
}

function getTableKeys($table) {
    global $mysqli;
    $result = $mysqli->query("select distinct
    column_name as 'thisColumn',
    concat(referenced_table_name, '.', referenced_column_name) as 'reference'
from
    information_schema.key_column_usage
where
    referenced_table_name is not null
and
	table_name = '" . $table . "'");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function generateDocumentationSyntax($multidimensionalArray) {
    $descriptions = include 'TableDetails.php';

    $htmlString = "<p class=\"lead\">This database contains " . sizeof($multidimensionalArray) . " tables.</p>";

    foreach($multidimensionalArray as $table) {
        $htmlString .= "
        <div class=\"panel-wrapper\">
            <div class=\"panel panel-default\">
                <div class=\"panel-heading jcorgFilterTextParent\">
                    <h3 class=\"panel-title search-content jcorgFilterTextChild\">" . $table['tableName'] . "</h3>
                    <span class=\"badge pull-right\">" . sizeof($table['rowInformation']) . " rows</span>
                </div>
                <div class=\"panel-body jcorgFilterTextParent\">
                    <p class=\"jcorgFilterTextChild\">
                        ";

        if(isset($descriptions[$table['tableName']])) {
            $htmlString .= $descriptions[$table['tableName']];
        } else {
            echo '<br>information for table ' . $table['tableName'] . ' required!';
        }

        $htmlString .= "
                    </p>
        
                    <div class=\"table-responsive\">
                        <table class=\"table table-striped\">
                            <tr>
                                <th class=\"col-md-4\">column</th>
                                <th class=\"col-md-3\">type</th>
                                <th class=\"col-md-5\">additional</th>
                            </tr>";
                            if(isset($table['rowInformation']))
                            foreach($table['rowInformation'] as $row) {
                                if($row[3] == 'PRI') {
                                    $htmlString .= "
                                    <tr id=\"" . $table['tableName'] . "." . $row[0] . "\" class=\"info\">
                                        <td class=\"col-md-4\"><span class=\"glyphicon glyphicon-asterisk\" aria-hidden=\"true\"></span> " . $row[0] . "</td>
                                        <td class=\"col-md-3\">" . $row[1] . "</td>";
                                    if(isset($row['foreignData'])) {
                                        $htmlString .= "<td class=\"col-md-5\">Primary key, Foreign key: <a href=\"#" . $row['foreignData'] . "\">" . $row['foreignData'] . "</a></td>
                                    </tr>";
                                    } else {
                                        $htmlString .= "<td class=\"col-md-5\">Primary key</td>
                                    </tr>";
                                    }
                                } else {
                                    $htmlString .= "
                                    <tr id=\"" . $table['tableName'] . "." . $row[0] . "\">
                                        <td class=\"col-md-4\">" . $row[0] . "</td>
                                        <td class=\"col-md-3\">" . $row[1] . "</td>";
                                    if(isset($row['foreignData'])) {
                                        $htmlString .= "<td class=\"col-md-5\">Foreign key: <a href=\"#" . $row['foreignData'] . "\">" . $row['foreignData'] . "</a></td>
                                    </tr>";
                                    } else {
                                        $htmlString .= "<td class=\"col-md-5\">&nbsp;</td>
                                    </tr>";
                                    }
                                }


                            }
                        $htmlString .= "    
                        </table>
                    </div>
                </div>
            </div>
        </div>";
    }

    //TODO add margin bottom im Footer
    //TODO Google Analytics
    //TODO Implement php array function
    //TODO implement as class
    //TODO outsource table data and mysql data
    //add get param with current version

    date_default_timezone_set('Europe/Berlin');
    $htmlString .= "<div class=\"text-right\">generated at " . date('d.m.Y h:i:s', time()) . ".</div>";

    return $htmlString;
}

$mysqli = new mysqli($HOST, $USER, $PASS);

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

$tables = null;

foreach($NAMES as $name) {
    echo "Inspection of " . $name . " starting. \n";
    $tables = getSQLTables($name);
    foreach($tables as &$tableValue) {
        $tableValue['rowInformation'] = getTableRows($tableValue['tableName']);
        $foreignKeyInformation = getTableKeys($tableValue['tableName']);
        foreach($foreignKeyInformation as $information) {
            foreach($tableValue['rowInformation'] as &$row) {
                if($row[0] == $information['thisColumn']) {
                    $row['foreignData'] = $information['reference'];
                }
            }
        }
    }
    echo "Inspection of " . $name . " finished successful. \n";

    $htmlString = generateDocumentationSyntax($tables);

    $var_str = substr(var_export($htmlString, true),1 , -1);
    file_put_contents('sw-versions/sw-' . substr($name, 9) . '.html', $var_str);
}