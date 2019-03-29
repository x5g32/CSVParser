# CSVParser
The PHP script "FileProcessor" takes CSV file data, processes it, and outputs a formatted table containing original data and some calculated statistics.

The script functions on the following stipulations:
- File must be in CSV format (.csv)
- Currency values are USD
- File must contain the following headers (in any order): SKU, Cost, Price, QTY
- The file may contain any or no other data

The script will output a table with the following statistics included:
- Profit Margin and Total Profit per item
- Average Cost, Average Price, Total Quantity, Average Profit Margin, and Total Profit

The script will also convert all currency values to a user specified currency.
In this implementation, the html form provides only two options: USD or CAD.
