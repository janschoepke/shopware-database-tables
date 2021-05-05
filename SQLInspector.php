<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

include 'mysqlData.php';

class SQLInspector {

    private $mysqli;

    //In this case the database is named xxxxxxxx_5_5_7, so the first 9 chars need to be cut.
    const VERSION_OFFSET = 9;

    //In this case the version number in the database name is represented by '_', e.g. 5_7_7.
    const VERSION_SEPARATOR = '_';

    public function __construct($HOST, $USER, $PASS) {
        $this->mysqli = new mysqli($HOST, $USER, $PASS);

        if ($this->mysqli->connect_errno) {
            printf("Connect failed: %s\n", $this->mysqli->connect_error);
            exit();
        }
    }

    private function getSQLTables($database){
        $result = $this->mysqli->select_db($database);
        
        $result = $this->mysqli->query("SELECT TABLE_NAME AS tableName FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . $database . "';");

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function getTableRows($table) {
        $result = $this->mysqli->query("DESCRIBE `" . $table . "`;");
        return $result->fetch_all(MYSQLI_NUM);
    }

    private function getTableKeys($table) {
        $result = $this->mysqli->query("select distinct
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

    private function generateDocumentationSyntax($multidimensionalArray) {
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
                echo 'information for table ' . $table['tableName'] . ' required!' . PHP_EOL;
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

        //TODO Google Analytics
        //add get param with current version

        date_default_timezone_set('Europe/Berlin');
        $htmlString .= "<div class=\"text-right\">generated at " . date('d.m.Y h:i:s', time()) . ".</div>";

        return $htmlString;
    }

    public function getDocumentationFiles($NAMES) {
        $tables = null;
        
        foreach($NAMES as $name) {
            echo "Inspection of " . $name . " starting. \n";
            $tables = $this->getSQLTables($name);
            foreach($tables as &$tableValue) {
                $tableValue['rowInformation'] = $this->getTableRows($tableValue['tableName']);
                $foreignKeyInformation = $this->getTableKeys($tableValue['tableName']);
                foreach($foreignKeyInformation as $information) {
                    foreach($tableValue['rowInformation'] as &$row) {
                        if($row[0] == $information['thisColumn']) {
                            $row['foreignData'] = $information['reference'];
                        }
                    }
                }
            }

            $htmlString = $this->generateDocumentationSyntax($tables);

            $var_str = substr(var_export($htmlString, true),1 , -1);
            file_put_contents('sw-versions/sw-' . str_replace(SQLInspector::VERSION_SEPARATOR, '-', substr($name, SQLInspector::VERSION_OFFSET)) . '.html', $var_str);

            echo "Inspection of " . $name . " finished successful. \n";
        }
    }

    public function getPHPArrayFiles($NAMES) {
        $tables = null;
        $result = null;

        foreach ($NAMES as $database) {
            echo "Building PHP Array for " . $database . ".\n";
            $tables = $this->getSQLTables($database);
            $tables = array_column($tables, 'tableName');

            foreach ($tables as $table) {
                $tableRows = $this->getTableRows($table);
                $result[$table] = array_column($tableRows, 0);
            }

            file_put_contents('array-data/sw-' . str_replace(SQLInspector::VERSION_SEPARATOR, '-', substr($database, SQLInspector::VERSION_OFFSET)) . '.json', json_encode($result));

            echo "Build of PHP Array for " . $database . " completed. \n";
        }
    }

    private function compareDatabases($file1, $file2) {
        $arr1 = json_decode(file_get_contents($file1), true);
        $arr2 = json_decode(file_get_contents($file2), true);

        $diff1 = array_map('unserialize',
            array_diff(array_map('serialize', $arr1), array_map('serialize', $arr2)));

        $diff2 = array_map('unserialize',
            array_diff(array_map('serialize', $arr2), array_map('serialize', $arr1)));

        $totalDiff = array_merge($diff1, $diff2);

        return $totalDiff;
    }

    public function createSyncMatrixFiles ($NAMES) {

        $path    = 'array-data';
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($NAMES as $name) {
            $currentVersionString = str_replace(SQLInspector::VERSION_SEPARATOR, '-', substr($name, SQLInspector::VERSION_OFFSET));

            if(in_array("sw-" . $currentVersionString . ".json", $files)) {
                echo "Building Sync Matrix for " . $name . ".\n";
                $syncMatrix = [];
                foreach($files as $file) {
                    if($file === 'sw-' . $currentVersionString . '.json') {
                        continue;
                    }

                    //Make sure, that only instances with same major version are compared (e.g. prevent comparison of Shopware 5 schema to Shopware 6 schema)
                    $currentFile = explode("-", $file);
                    $currentVersion = explode("-", $currentVersionString);
                    if($currentFile[1] !== $currentVersion[0]) {
                        continue;
                    }

                    $syncMatrix[str_replace('.json', '', $file)] = array_keys($this->compareDatabases("array-data/sw-" . $currentVersionString . ".json", "array-data/" . $file));
                }
                file_put_contents('compare-data/' . $currentVersionString . '.json', json_encode($syncMatrix));
                echo "Build of Sync Matrix for " . $name . " completed. \n";
            }
        }
    }
}

$sqlInspector = new SQLInspector($HOST, $USER, $PASS);
$sqlInspector->getDocumentationFiles($NAMES);
$sqlInspector->getPHPArrayFiles($NAMES);
$sqlInspector->createSyncMatrixFiles($NAMES);


