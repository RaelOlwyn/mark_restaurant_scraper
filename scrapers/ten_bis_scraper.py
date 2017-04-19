# -*- coding: utf-8 -*-
import csv
import requests
from BeautifulSoup import BeautifulSoup
import os
import urlparse
import urllib
import urllib2
import httplib2
import gzip
import json
import smtplib
import base64
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication
from httplib2 import FileCache
from urllib2 import HTTPRedirectHandler, HTTPDefaultErrorHandler, HTTPError
import re

### Hard-coded variables ###

api = 'AIzaSyARECBmtglGxkXLENmHE1Pwshf3Mg4zSJU'

languages = ["af", "sq", "ar","be", "bg", "ca", "zh-CN", "zh-TW", "hr",
             "cs", "da", "nl", "en", "et", "tl", "fi", "fr", "gl", "de",
             "el", "iw", "hi", "hu", "is", "id", "ga", "it", "ja", "ko",
             "lv", "lt", "mk", "ms", "mt", "no", "fa", "pl", "pt", "ro",
             "ru", "sr", "sk", "sl", "es", "sw", "sv", "th", "tr", "uk",
             "vi", "cy", "yi"]

def _validate_language(lang):
    if lang in languages:
        return True
    return False

### Custom G-Zipped Cache ###

def save_cached_key(path, value):
    f = gzip.open(path, 'wb')
    f.write(value)
    f.close()

def load_cached_key(key):
    f = gzip.open(key)
    retval = f.read()
    f.close()
    return retval

class ZipCache(FileCache):
    def __init__(self, cache='.cache'): #TODO: allow user configurable?
        super(ZipCache, self).__init__(cache)

    def get(self, key):
        cacheFullPath = os.path.join(self.cache, self.safe(key))
        retval = None
        try:
            retval = load_cached_key(cacheFullPath)
        except IOError:
            pass
        return retval

    def set(self, key, value):
        retval = None
        cacheFullPath = os.path.join(self.cache, self.safe(key))
        save_cached_key(cacheFullPath, value)

### Error Handlers ###

class DefaultErrorHandler(HTTPDefaultErrorHandler):
    def http_error_default(self, req, fp, code, msg, headers):
        result = HTTPError(req.get_full_url(), code, msg, headers, fp)
        result.status = code
        return result


class RedirectHandler(HTTPRedirectHandler):
    def http_error_301(self, req, fp, code, msg, headers):
        result = HTTPRedirectHandler.http_error_301(self, req, fp, code,
                        msg, headers)
        result.status = code
        return result

    def http_error_302(self, req, fp, code, msg, headers):
        results = HTTPRedirectHandler.http_error_302(self, req, fp, code,
                        msg, headers)
        results.status = code
        return result

### Translator Class ###

class GoogleTranslator(object):

    def __init__(self):
        #NOTE: caching is done on etag not expiry
        self.cache_control = 'max-age='+str(7 * 24 * 60 * 60)
        self.connection = httplib2.Http(ZipCache())
        self._opener = urllib2.build_opener(DefaultErrorHandler,
                                            RedirectHandler)
        self.base_url = "https://www.googleapis.com/language/translate/v2/"

    def _urlencode(self, params):
        """
        Rewrite urllib.urlencode to handle string input verbatim
        """
        params = "&".join(map("=".join,params))
        return params

    def _build_uri(self, extra_url, params):
        params = [('key', api)] + params
        params = self._urlencode(params)
        url = "%s?%s" % (urlparse.urljoin(self.base_url, extra_url), params)
        if len(url) > 2000: # for GET requests only, POST is 5K
            raise ValueError("Query is too long. URL can only be 2000 "
                             "characters")
        return url

    def _fetch_data(self, url):
        connection = self.connection
        resp, content = connection.request(url, headers={'user-agent' : api,
                            'cache-control' : self.cache_control})
        #DEBUG
        #if resp.fromcache:
        #   print "Using from the cache"
        return content

    def _sanitize_query(self, query):
        if isinstance(query, (list,tuple)):
            query = zip('q' * len(query), map(urllib.quote,query))
        else:
            query = [('q',urllib.quote(query))]
        return query

    def _decode_json(self, response):
        """
        Assumes that response only holds one result
        """
        json_data = json.loads(response)
        try:
            data = json_data["data"]
            if 'translations' in data:
                return data['translations']
            elif 'detections' in data:
                return data['detections']
        except:
            if 'error' in json_data:
                return json_data["error"]


    def detect(self, query):
        query = self._sanitize_query(query)
        url = self._build_uri(extra_url='detect/', params=query)
        content = self._fetch_data(url)
        # going to have json, decode it first
        return self._decode_json(content)

    def translate(self, query, target="en", source="he", _dirty=False):
        try:
            assert _validate_language(target)
        except:
            raise ValueError("target language %s is not valid" % target)
        newquery = self._sanitize_query(query)
        params = [('key', api), ('target' , target)]
        if source:
            try:
                assert _validate_language(target)
            except:
                raise ValueError("source language %s is not valid" % target)
            params += ["source", source]
        params += newquery
        url = self._build_uri("", params)
        content = self._fetch_data(url)
        results = self._decode_json(content)

        if "errors" in results and not _dirty:
            if results['message'] == 'Bad language pair: {0}':
                # try to detect language and resubmit query
                source = self.detect(query)
                source = source[0]['language']
                return self.translate(query, target, source, True)

        return results

translator = GoogleTranslator()

def send_email(attachment_filename, body, from_email, from_email_password, to_emails, subject):
    msg = MIMEMultipart()

    filename = attachment_filename

    part = MIMEApplication(open(filename).read())
    part.add_header('Content-Disposition', 'attachment; filename="%s"' % os.path.basename(filename))
    msg.attach(part)

    msg['Subject'] = subject
    msg['From'] = from_email
    msg['To'] = to_emails
    msg.attach(MIMEText(body))

    server = smtplib.SMTP('smtp.gmail.com', 587)
    server.starttls()
    server.login(from_email, from_email_password)
    server.sendmail(from_email, [to_emails], msg.as_string())
    server.quit()

#############################################################################################
#scraper 

url = 'https://www.10bis.co.il/Restaurants/Menu/Delivery?resId=13976&couponValue=0'
response = requests.get(url)
html = response.content
span_array = []

soup = BeautifulSoup(html)

#get csv file name from website
restaurant_name_div = soup.find('span', attrs={'class': 'ResNameHeader'})
file_name = re.search(r'(.+)', restaurant_name_div.text, flags=0).group(1).encode('UTF-8')
file_name = file_name.replace(' ', '_')
print file_name
    
list_of_sections = []
menu = soup.find('div', attrs={'class': 'menuMainTbl'})
sections = soup.findAll('div', attrs={'class': 'menuMainTbl'})

for section in sections:

    list_of_rows = []

    titles = section.findAll('div', attrs={'class': 'CategoryName'})
    rows = section.findAll('div', attrs={'class': 'row menuMainTbl dishesTable'})

    for x in xrange(0,len(titles)):

        title_header = titles[x].text.replace('&quot;', '"').replace('&#39;', "'").encode('UTF-8')
        row = rows[x]

        for box in row.findAll('div', attrs={'class': 'dishesBox  '}):

            list_of_cells = []

            for cell in box.findAll('p'):
                list_of_cells.append(title_header)

                text = cell.text.replace('&nbsp;', '').replace('&quot;', '"').replace('&#39;', "'").encode('UTF-8')
                list_of_cells.append(text)
                #print text 

            description = box['title'].encode('UTF-8').replace('.', '')
            list_of_cells.append(description)
            #print description 

            for cell in box.findAll('div', attrs={'class': 'dishPriceDiv'}):
                text = cell.text.replace('&nbsp;', '').replace('+', '').encode('UTF-8').replace('₪', '')
                list_of_cells.append(text)
                #print text

            #translations
            for cell in box.findAll('p'):
                translation = translator.translate(title_header)
                list_of_cells.append(format(translation[0]['translatedText']))

                text = cell.text.replace('&nbsp;', '').replace('&quot;', '"').replace('&#39;', "'").encode('UTF-8')
                translation = translator.translate(text)
                list_of_cells.append(format(translation[0]['translatedText'].replace('&#39;', "'")))
                #print translation 

            description = box['title'].encode('UTF-8').replace('.', '')
            translation = translator.translate(description)
            list_of_cells.append(format(translation[0]['translatedText'].replace('&#39;', "'")))
            #print translation 

            list_of_rows.append(list_of_cells)

    list_of_sections.append(list_of_rows)

full_file_path = "./csv_generated/" + file_name + '.csv'
outfile = open(full_file_path, "wb")

fieldnames = ['HE Category', 'HE Title', 'HE Description', 'HE Price', 'EN Category', 'EN Title', 'EN Description', ]
header_writer = csv.DictWriter(outfile, fieldnames=fieldnames)
header_writer.writeheader()

writer = csv.writer(outfile)
for row_list in list_of_sections:
    writer.writerows(row_list)

msg = "Things to look out for: "
msg += "\n-Unecessarily wordy hebrew titles"
msg += "\n-english translation errors"
msg += "\n-price differences between this website and the restaurants in-house menu"
msg += "\n-sauces and addons are not always included"
msg += "\n\n Original URL: " + url

send_email(full_file_path, msg, 'mark@pushstartups.com', 'zdhconsulting2', 'alizafaber@gmail.com', file_name)
