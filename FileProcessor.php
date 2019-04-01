<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * CSV File Processor
 * 
 * Requirements:
 * - File is .csv format
 * - Currency values within the file are USD
 * - The following headers are required (in any order): SKU, Cost, Price, QTY
 * 
 * Example implementation:
 * $file_path = 'C:\Users\User\Documents\test.csv';
 * $currency = 'CAD';
 * $processor = new FileProcessor($file_path,$currency);
 * $processor->renderTable();
 * 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class FileProcessor {

    // ********************************************************************************
    // Properties and Constructor
    // ********************************************************************************

    // class properties
    public  $filepath;
    private $file;
    private $cols;
    public $rows;
    private $header;
    private $currency;
    private $stats = array();

    // header array index IDs (to maintain column order) 
    private $skuID;
    private $costID;
    private $priceID;
    private $qtyID;

    // constructor: set those props!
    function __construct($file_path,$currency) {
        $this->filepath = $file_path;
        $this->setCurrency($currency);
        $this->openFile();
        $this->setHeader();
        $this->setHeaderIDs();
        $this->setRowsCols();
        $this->setStats();
    }


    // ********************************************************************************
    // METHODS
    // ********************************************************************************

    // ****************************************************************
    // method for opening the file (read only mode)
    // ****************************************************************
    private function openFile() {
        $this->file = fopen($this->filepath,"r");
    }


    // ****************************************************************
    // method for closing the file
    // ****************************************************************
    private function closeFile($pointer) {
        $close_file = fclose($pointer);
        return $close_file;
    }


    // ****************************************************************
    // method for setting the header
    // ****************************************************************
    private function setHeader() {
        $this->header = fgetcsv($this->file,0,",");

        // reset the file pointer
        fseek($this->file,0);
    }


    // ****************************************************************
    // method for setting specific header ids if they exist.
    // check for the four guaranteed headers: sku, cost, price, qty
    // ****************************************************************
    private function setHeaderIDs() {

        // get the header array to check
        $refheader = $this->header;

        // change header values to uppercase
        function toUpper(&$value,$key) {
            $value = strtoupper($value);
        }
        array_walk($refheader,'toUpper');

        // look for specific header values and set them
        if(in_array('SKU',$refheader,true)) {
            $this->skuID = array_search('SKU',$refheader);
        } else {
            $this->skuID = 0;
        };

        if(in_array('COST',$refheader,true)) {
            $this->costID = array_search('COST',$refheader);
        } else {
            $this->costID = 0;
        };

        if(in_array('PRICE',$refheader,true)) {
            $this->priceID = array_search('PRICE',$refheader);
        } else {
            $this->priceID = 0;
        };

        if(in_array('QTY',$refheader,true)) {
            $this->qtyID = array_search('QTY',$refheader);
        } else {
            $this->qtyID = 0;
        };
    }


    // ****************************************************************
    // method for setting number of rows and cols
    // ****************************************************************
    private function setRowsCols() {

        // get the number of columns
        $this->cols = count($this->header);

        // get the number of rows (not including the header)
        $rowcount = 0;
        while((fgetcsv($this->file,0,",")) !== FALSE) {
            $rowcount++;
        }
        $rows = $rowcount - 1;
        $this->rows = $rows;

        // reset the file pointer
        fseek($this->file,0);
    }


    // ****************************************************************
    // method for setting the default currency
    // ****************************************************************
    private function setCurrency($currency) {
        if($currency !== 'USD' && $currency !== 'CAD') {
            $this->currency = 'USD';
        }
        if($currency == 'CAD') {
            $this->currency = 'CAD';
        } else {
            $this->currency = 'USD';
        }
    }


    // ****************************************************************
    // method for converting USD to CAD
    // ****************************************************************
    protected function convCurrency($value) {

        // set API Endpoint, access key, required parameters
        $endpoint = 'convert';
        $access_key = '516607d6152d4eebecb8f18bdbb5c09c';

        $from = 'USD';
        $to = $this->currency;
        $amount = $value;

        // initialize CURL:
        $ch = curl_init('http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key.'&from='.$from.'&to='.$to.'&amount='.$amount.'');   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // get the JSON data:
        $json = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response:
        $conversionResult = json_decode($json, true);

        // access the conversion result
        if(array_key_exists('result',$conversionResult)) {
            $result = $conversionResult['result'];
            return $result;
        } else {
            return $value;
        }
    }


    // ****************************************************************
    // method for getting the average cost, average price,
    // total quantity, average profit margin, and total profit
    // stats are returned as an array
    // ****************************************************************
    private function setStats() {
        $cost_sum = 0;
        $price_sum = 0;
        $qty_sum = 0;
        $pm_sum = 0;

        // look at each row and build some stats
        while(($row = fgetcsv($this->file,0,",")) !== FALSE) {

            // validate cost, price, and qty
            $check_cost = $row[$this->costID];
            $check_price = $row[$this->priceID];
            $check_qty = $row[$this->qtyID];
            if(!is_numeric($check_cost)) {
                $check_cost = 0;
            }
            if(!is_numeric($check_price)) {
                $check_price = 0;
            }
            if(!is_numeric($check_qty)) {
                $check_qty = 0;
            }

            // convert currency for cost and price
            if($this->currency !== 'USD') {
                $cost = round($this->convCurrency($check_cost),2);
                $price = round($this->convCurrency($check_price),2);
                $qty = round($this->convCurrency($check_qty),2);
            } else {
                $cost = $check_cost;
                $price = $check_price;
                $qty = $check_qty;                 
            }

            // sum all the cost values
            $cost_sum += $cost;

            // sum all the price values
            $price_sum += $price;
            
            // sum all the quantity values
            $qty_sum += $qty;

            // sum profit margins
            $pm_sum += $this->getProfitMargin($cost,$price,$qty);
        }

        // calc avg price and put into the stats array
        $this->stats['Avg Price'] = number_format(round(($price_sum / $this->rows),2),2);

        // calc total quantity and put into the stats array
        $this->stats['Total Quantity'] = $qty_sum;

        // calc average profit margin and put into the stats array
        $this->stats['Avg Profit Margin'] = number_format(round(($pm_sum / $this->rows),2),2);

        // calc total cost and put into stats array
        $this->stats['Total Cost'] = number_format($cost_sum,2);

        // calc average cost and put into stats array
        $this->stats['Average Cost'] = number_format(round(($cost_sum / $this->rows),2),2);

        // reset the file pointer
        fseek($this->file,0);
    }


    // ****************************************************************
    // method for calculating profit margin based on item cost,
    // price, and quantity
    // ****************************************************************
    protected function getProfitMargin($cost,$price,$qty) {
        if(!is_numeric($cost)) {
            $cost = 0;
        }
        if(!is_numeric($price)) {
            $price = 0;
        }
        if(!is_numeric($qty)) {
            $qty = 0;
        }
        $total_cost = $cost * $qty;
        $total_profit = $price * $qty;
        $profit_margin = ($total_profit - $total_cost) / 100;
        return $profit_margin;
    }


    // ****************************************************************
    // method for returning the formatted header row
    // ****************************************************************
    private function renderHeader() {
        $build_header = '
            <table>
            <tr>
            <th>Row ID</th>
            <th>SKU</th>
            <th>Cost (' . $this->currency . ')</th>
            <th>Price (' . $this->currency . ')</th>
            <th>QTY</th>
            <th>Profit Margin</th>
            <th>Total Profit (' . $this->currency . ')</th>';

        // place extra columns here
        if($this->cols > 4) {

            // get the header array to check
            $refheader = $this->header;

            // loop through all the header values
            $i = 0;
            while($this->cols > $i) {
                $value = current($refheader);

                // if the current value is not one of the known headers, add it
                if($value !== $this->header[$this->skuID] &&
                   $value !== $this->header[$this->costID] &&
                   $value !== $this->header[$this->priceID] &&
                   $value !== $this->header[$this->qtyID]) {
                       $build_header .= '
                       <th>' . $value . '</th>';
                   }
                
                // point to the next value in the array
                next($refheader);
                $i++;
            }
        }

        $build_header .= '</tr>';
        echo $build_header;
    }


    // ****************************************************************
    // method for returning the formatted body of the table
    // ****************************************************************
    private function renderBody() {

        $build_body = '';
        $profit_sum = 0;
        $rowid = 0;
        while(($row = fgetcsv($this->file,0,",")) !== FALSE) {

            // skip the header (which is the first row)
            if($rowid !== 0) {

                // get and validate some info from the current row
                $check_cost = $row[$this->costID];
                $check_price = $row[$this->priceID];
                $check_qty = $row[$this->qtyID];

                if(!is_numeric($check_cost)) {
                    $check_cost = 0;
                }
                if(!is_numeric($check_price)) {
                    $check_price = 0;
                }
                if(!is_numeric($check_qty)) {
                    $qty = 0;
                } else {
                    $qty = $check_qty;
                }

                if($this->currency !== 'USD') {
                    $convert_cost = $this->convCurrency($check_cost);
                    $convert_price = $this->convCurrency($check_price);
                    $cost = $convert_cost;
                    $price = $convert_price;
                } else {
                    $cost = $check_cost;
                    $price = $check_price;
                }

                // calculate total profit (price * qty) for current row (item)
                $total_profit = round(($price * $qty),2);

                // sum total profit of all rows for reference later
                $profit_sum += $total_profit;

                // calculate profit margin for current row (item)
                $profit_margin = round($this->getProfitMargin($cost,$price,$qty),2);

                // check if quantity, profit margin, and total profit are negative
                ($qty < 0) ? $qty_class = 'red' : $qty_class = 'green';
                ($profit_margin < 0) ? $pm_class = 'red' : $pm_class = 'green';
                ($total_profit < 0) ? $tp_class = 'red' : $tp_class = 'green';

                // build the body of the table
                $build_body .= '
                    <tr>
                    <td>' . $rowid . '</td>
                    <td>' . $row[$this->skuID] . '</td>
                    <td>$' . number_format($cost,2) . '</td>
                    <td>$' . number_format($price,2) . '</td>
                    <td class="' . $qty_class . '">' . $qty . '</td>
                    <td class="' . $pm_class . '">' . number_format($profit_margin,2) . '%</td>
                    <td class="' . $tp_class . '">$' . number_format($total_profit,2) . '</td>';

                // place extra columns here
                if($this->cols > 4) {

                    // point the array position to the beginning of the row
                    reset($row);

                    // loop through all the row values
                    $i = 0;
                    while($this->cols > $i) {

                        // get the current index id
                        $key = key($row);

                        // if the current key does not belong to one of the known headers, add it
                        if($key !== $this->skuID && $key !== $this->costID && $key !== $this->priceID && $key !== $this->qtyID) {
                            $build_body .= '<td>' . current($row) . '</td>';
                        }
                        
                        // point to the next value in the row
                        next($row);
                        $i++;
                    }
                }
                $build_body .= '</tr>';
            }
            $rowid++;
        }

        // add total profit summed from all rows to stats array
        $this->stats['Total Profit'] = number_format($profit_sum,2);

        // reset the file pointer
        fseek($this->file,0);

        // all done building the body of the table
        echo $build_body;
    }


    // ****************************************************************
    // method for returning the formatted footer row with stats
    // ****************************************************************
    private function renderFooter() {
        $build_footer = '
            <tr>
            <td></td>
            <td></td>
            <td>Average Cost (' . $this->currency . ')<br />$' . $this->stats['Average Cost'] . '</td>
            <td>Average Price (' . $this->currency . ')<br />$' . $this->stats['Avg Price'] . '</td>
            <td>Total Quantity<br />' . $this->stats['Total Quantity'] . '</td>
            <td>Average Profit Margin<br />' . $this->stats['Avg Profit Margin'] . '%</td>
            <td>Total Profit (' . $this->currency . ')<br />$' . $this->stats['Total Profit'] . '</td>';

        $build_footer .= '</tr></table>';
        echo $build_footer;
    }


    // ****************************************************************
    // method for rendering the whole table
    // ****************************************************************
    public function renderTable() {
        $render_table = $this->renderHeader() . $this->renderBody() . $this->renderFooter();

        // close the file before outputting
        $close = $this->closeFile($this->file);

        // output the table!
        echo $render_table;
    }
}
?>
