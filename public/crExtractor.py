
import camelot
import json
import glob
import mysql.connector
import os
import random
import sys

headings = camelot.read_pdf(str(sys.argv[1]), multiple_tables=True, columns=['100,150,200,250,300,350,400,450,500,550,600,650,700,750,800'], pages="all", flavor="stream", edge_tol=10, row_tol=16)

for h in range(headings.n):
    headings[h].parsing_report
    headings[h].to_json('../jsons/head'+str(h)+'.json')

result = []
for f in glob.glob("../jsons/*.json"):
    with open(f, "rb") as infile:
        try:
            result.append(json.load(infile))
        except ValueError:
            print(f)

with open("merged_file.json", "w") as outfile:
    json.dump(result, outfile)


jsonData = json.dumps(result)

files = glob.glob('../jsons/*')

for f in files:
    os.remove(f)

files = glob.glob('../public/pdfcr/*')
for f in files:
    os.remove(f)

print(jsonData)
