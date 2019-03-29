<?php
include('FileProcessor.php');
?>
<html>
<header>
  <title>CSV Parser</title>
  <link rel="stylesheet" type="text/css" href="styles.css">
</header>
<body>

<div class="wrapper">
    <div class="topbox">
        <div class="info">
            <h1>CSV Processor</h1>
            <p>This form will submit a CSV file to be processed by a PHP script. This script functions on the following stipulations:</p>
            <ul class="list">
                <li>File must be in CSV format (.csv)</li>
                <li>Currency values are USD</li>
                <li>File must contain the following headers (in any order):<br />
                    <ul>
                        <li>SKU, Cost, Price, QTY</li>
                    </ul>
                </li>
                <li>The file may contain any or no other data</li>
            </ul>
        </div>

        <div class="formbox">
            <form enctype="multipart/form-data" action="index.php" method="POST">
                <table>
                    <tr>
                        <td colspan="3">
                            <!-- MAX_FILE_SIZE must precede the file input field -->
                            <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                            <input class="browse" name="userfile" type="file" />
                        </td>
                    </tr>
                    <tr>
                        <td>Output Currency:</td>
                        <td><label for="USD">USD</label><input type="radio" name="currency" id="USD" value="USD" <?php if(isset($_POST['currency']) && $_POST['currency'] == 'USD') { echo 'checked'; } else { echo 'checked'; } ?> /></td>
                        <td><label for="CAD">CAD</label><input type="radio" name="currency" id="CAD" value="CAD" <?php if(isset($_POST['currency']) && $_POST['currency'] == 'CAD') { echo 'checked'; } ?> /></td>
                    </tr>
                    <tr>
                        <td><input class="submit" type="submit" name="submit" value="Submit" /></td>
                    </tr>
                </table>
            </form>
            <form action="index.php" method="POST">
                <input type="submit" name="reset" value="Reset Page" />
            </form>
        </div>
    </div>

    <div class="showtable">
        <?php
        if(isset($_POST['submit']) && $_FILES['userfile']['error'] == '0') {
            echo 'File uploaded: ' . $_FILES['userfile']['name'] . '<br /><br />';
            $file_path = $_FILES['userfile']['tmp_name'];
            $processor = new FileProcessor($file_path,$_POST['currency']);
            $processor->renderTable();
        }
        else {
            if(isset($_POST['submit']) && $_FILES['userfile']['error'] == '4') {
                echo '<p>No file was submitted</p>';
            }
            echo '<p>After file upload, the PHP script will parse the CSV data and output a table in the following format:</p>
            <br />
            <table>
                <tr>
                    <th>Row ID</th>
                    <th>SKU</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>QTY</th>
                    <th>Profit Margin</th>
                    <th>Total Profit</th>
                    <th>Any additional columns..</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>1</td>
                    <td>$0.00</td>
                    <td>$0.00</td>
                    <td>##</td>
                    <td>0.00%</td>
                    <td>$0.00</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>2</td>
                    <td>$0.00</td>
                    <td>$0.00</td>
                    <td>##</td>
                    <td>0.00%</td>
                    <td>$0.00</td>
                    <td>...</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Average Price<br />$0.00</td>
                    <td>Total Quantity<br />##</td>
                    <td>Average Profit Margin<br />0.00%</td>
                    <td>Total Profit<br />$0.00</td>
                </tr>
            </table>';
        }
        ?>
    </div>
</div>
</body>
</html>