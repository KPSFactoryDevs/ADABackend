import camelot
import json
import glob
import mysql.connector
import os
import random
import sys

headings = camelot.read_pdf(str(sys.argv[1]), multiple_tables=True, columns=['100,150,200,250,300,350,400,450,500,550,600,650,700,750,800'], pages=sys.argv[2], flavor="stream", edge_tol=10, row_tol=16)

headings[0].parsing_report
headings[0].to_json('./jsons/head.json')
result = []
with open('./jsons/head.json') as json_file:
    try:
        result.append(json.load(json_file))
    except ValueError:
        print(f)

with open("merged_file.json", "w") as outfile:
    json.dump(result, outfile)

jsonData = json.dumps(result)
print(jsonData)
