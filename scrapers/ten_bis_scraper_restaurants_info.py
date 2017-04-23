# -*- coding: utf-8 -*-
import time
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

list_of_rows = []

for x in xrange(0,14085): #14085

    url = 'https://www.10bis.co.il/Restaurants/Menu/Delivery/' + str(x)
    response = requests.get(url)
    html = response.content
    span_array = []

    soup = BeautifulSoup(html)

    if soup.find('title').text == "500 - Internal server error.":
        continue


    list_of_cells = []

    div_list = []

    restaurant_details_div = soup.find('div', attrs={'class': 'ResDetailsDiv'})

    for div in restaurant_details_div:
        div_list.append(div)

    restaurant_city = soup.find('meta', attrs={'itemprop': 'addressLocality'})['content']
    restaurant_address = soup.find('meta', attrs={'itemprop': 'streetAddress'})['content']
    restaurant_name = soup.find('span', attrs={'class': 'ResNameHeader'}).text.replace('&nbsp;', '').replace('&quot;', '"').replace('&#39;', "'").replace('&amp;', "'")
    restaurant_url = soup.find('meta', attrs={'itemprop': 'menu'})['content']
    restaurant_category = div_list[3].text

    for div in restaurant_details_div:
        div_list.append(div)

#    print restaurant_name

    list_of_cells.append(restaurant_city.encode('UTF-8'))
    list_of_cells.append(restaurant_address.encode('UTF-8'))
    list_of_cells.append(restaurant_name.encode('UTF-8'))
    list_of_cells.append(restaurant_url.encode('UTF-8'))
    list_of_cells.append(restaurant_category.encode('UTF-8'))

    list_of_rows.append(list_of_cells)

full_file_path = "./restaurants.csv"
outfile = open(full_file_path, "wb")

fieldnames = ['City', 'Address', 'Restaurant Name', 'Link', 'Category']
header_writer = csv.DictWriter(outfile, fieldnames=fieldnames)
header_writer.writeheader()

writer = csv.writer(outfile)
writer.writerows(list_of_rows)

time.sleep(5)

#attachment_filename, body, from_email, from_email_password, to_emails, subject
send_email(full_file_path, "Here is a little list of restaurant info in israel", 'mark@pushstartups.com', 'zdhconsulting2', 'mcheirif@gmail.com', 'Restaurant list CSV')
