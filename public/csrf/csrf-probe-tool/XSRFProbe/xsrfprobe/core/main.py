#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# -:-:-:-:-:-:-:-:-:#
#    XSRFProbe     #
# -:-:-:-:-:-:-:-:-:#

# Author: 0xInfection
# This module requires XSRFProbe
# https://github.com/0xInfection/XSRFProbe

# Standard Package imports
import os
import sys
import ssl
import time
import warnings
import http.cookiejar
from bs4 import BeautifulSoup

# This needs to be first, so that the options are loaded, as well as things like
#  colors are disabled
import xsrfprobe.core.options

import xsrfprobe.core.colors

colors = xsrfprobe.core.colors.color()


try:
    from urllib.parse import urlencode
    from urllib.error import HTTPError, URLError
    from urllib.request import build_opener, HTTPCookieProcessor, HTTPSHandler
except ImportError:  # Throws exception in Case of Python2
    print(
        f"{colors.RED} [-] {colors.ORANGE}XSRFProbe{colors.END} isn't compatible with Python 2.x versions.\n"
        f"{colors.RED} [-] {colors.END}Use Python 3.x to run {colors.ORANGE}XSRFProbe."
    )
    quit()

try:
    import requests, stringdist, bs4
except ImportError:
    print(
        " [-] Required dependencies are not installed.\n"
        " [-] Run {colors.ORANGE}pip3 install -r requirements.txt{colors.END} to fix it."
    )

# Imports from core
from xsrfprobe.files.discovered import FORMS_TESTED

from xsrfprobe.core.inputin import inputin
from xsrfprobe.core.request import Get, Post
from xsrfprobe.core.verbout import verbout
from xsrfprobe.core.prettify import formPrettify
from xsrfprobe.core.banner import banner, banabout
from xsrfprobe.core.forms import testFormx1, testFormx2
from xsrfprobe.core.logger import ErrorLogger, GetLogger
from xsrfprobe.core.logger import VulnLogger, NovulLogger

# Imports from files
from xsrfprobe.files.config import (
    VERIFY_CERT,
    COOKIE_VALUE,
    HEADER_VALUES,
    CRAWL_SITE,
    REFERER_ORIGIN_CHECKS,
    FORM_SUBMISSION,
    COOKIE_BASED,
    POST_BASED,
)

# Imports from modules
from xsrfprobe.modules import Debugger
from xsrfprobe.modules import Parser
from xsrfprobe.modules import Crawler
from xsrfprobe.modules.Origin import Origin
from xsrfprobe.modules.Cookie import Cookie
from xsrfprobe.modules.Tamper import Tamper
from xsrfprobe.modules.Entropy import Entropy
from xsrfprobe.modules.Referer import Referer
from xsrfprobe.modules.Encoding import Encoding
from xsrfprobe.modules.Analysis import Analysis
from xsrfprobe.modules.Checkpost import PostBased

# Import Ends

# First rule, remove the warnings!
warnings.filterwarnings("ignore")


import pdb; pdb.set_trace()
from xsrfprobe.corsfetcher import fetch_client_info
from xsrfprobe.files import config
def Engine():  # lets begin it!
    os.system("clear")  # Clear terminal :p
    banner()  # Print the banner
    banabout()  # The second banner
    web, fld = inputin()  # Take the input
    
# Auto-fetch client info if --client-url is provided
    config.DEBUG = True  # Add this after imports in main.py
    if config.CLIENT_URL:
        verbout(colors.O, f"Auto-fetching session info from client: {config.CLIENT_URL}")
        cookies, csrf = fetch_client_info(config.CLIENT_URL)
        if cookies:
            # Merge fetched cookies into COOKIE_VALUE
            cookie_str = "; ".join([f"{k}={v}" for k, v in cookies.items()])
            config.COOKIE_VALUE.append(cookie_str)
            verbout(colors.G, f"Using cookies: {cookie_str}")
        if csrf:
            config.HEADER_VALUES['X-XSRF-Token'] = csrf
            verbout(colors.G, f"Using CSRF token: {csrf}")
        else:
            verbout(colors.O, "No CSRF token fetched; proceeding with scan")

    form1 = testFormx1()  # Test form #1
    form2 = testFormx2()  # Test form #2

    # Prepare CookieJars for the requests
    cookie0 = http.cookiejar.CookieJar()  # user #1
    cookie1 = http.cookiejar.CookieJar()  # user #2

    # If certificate verification is disabled
    # (some local dev servers or self-signed)
    if not VERIFY_CERT:
        # Create an unverified context
        context = ssl._create_unverified_context()
        ssl_handler = HTTPSHandler(context=context)
        resp1 = build_opener(HTTPCookieProcessor(cookie0), ssl_handler)
        resp2 = build_opener(HTTPCookieProcessor(cookie1), ssl_handler)
    else:
        resp1 = build_opener(HTTPCookieProcessor(cookie0))
        resp2 = build_opener(HTTPCookieProcessor(cookie1))

    action_done = []
    csrf = ""
    ref_detect = 0x00
    ori_detect = 0x00

    # Initialize the form debugger
    form = Debugger.Form_Debugger()
    bs1 = BeautifulSoup(form1, "html.parser").findAll("form", action=True)[0]
    bs2 = BeautifulSoup(form2, "html.parser").findAll("form", action=True)[0]

    init1 = web
    hdrs = [("Cookie", ",".join(cookie for cookie in COOKIE_VALUE))]
    [hdrs.append((k, v)) for k, v in HEADER_VALUES.items()]
    resp1.addheaders = resp2.addheaders = hdrs

    # Make initial requests as user #1 and user #2
    try:
        resp1.open(init1)
        resp2.open(init1)
    except HTTPError as e:
        verbout(colors.R, f"Initial open() error: {e}")
        ErrorLogger(init1, str(e))
    except URLError as e:
        verbout(colors.R, f"Initial open() error: {e}")
        ErrorLogger(init1, str(e))

    # If not crawling, we test only the single endpoint
    try:
        if not CRAWL_SITE:
            url = web
            try:
                r = Get(url)
                if r is not None:
                    response = r.text
                else:
                    response = ""
                verbout(colors.O, "Trying to parse response...")
                soup = BeautifulSoup(response, "html.parser")
            except AttributeError:
                verbout(colors.R, "No response received, site probably down: " + url)
            i = 0  # user index

            # If referer/origin checks are enabled, test them
            if REFERER_ORIGIN_CHECKS:
                verbout(
                    colors.O,
                    f"Checking endpoint request validation via {colors.GREY}Referer{colors.END}..."
                )
                if Referer(url):
                    ref_detect = 0x01

                verbout(colors.O, "Confirming the vulnerability...")

                verbout(
                    colors.O,
                    f"Confirming endpoint request validation via {colors.GREY}Origin{colors.END}..."
                )
                if Origin(url):
                    ori_detect = 0x01

            # Now parse forms from that single page
            verbout(
                colors.O,
                f"Retrieving all forms on {colors.GREY}{url}{colors.END}...",
            )

            for m in Debugger.getAllForms(soup):
                verbout(colors.O, "Testing form:\n" + colors.CYAN)
                formPrettify(m.prettify())
                verbout("", "")
                FORMS_TESTED.append("(i) " + url + ":\n\n" + m.prettify() + "\n")

                try:
                    if not m.get("action"):
                        # if missing "action" attribute
                        m["action"] = "/" + url.rsplit("/", 1)[1]
                        ErrorLogger(url, 'No standard form "action".')
                except KeyError:
                    m["action"] = "/" + url.rsplit("/", 1)[1]
                    ErrorLogger(url, 'No standard form "action".')

                action = Parser.buildAction(url, m["action"])
                if action and action not in action_done:
                    if FORM_SUBMISSION:
                        try:
                            # User #1
                            result, genpoc = form.prepareFormInputs(m)
                            r1 = Post(url, action, result)

                            # User #2
                            result, genpoc = form.prepareFormInputs(m)
                            r2 = Post(url, action, result)

                            if COOKIE_BASED:
                                Cookie(url, r1)

                            # Check anti-CSRF token entropy
                            try:
                                if "name" in m.attrs:
                                    query, token = Entropy(
                                        result,
                                        url,
                                        r1.headers,
                                        m.prettify(),
                                        m["action"],
                                        m["name"],
                                    )
                                else:
                                    query, token = Entropy(
                                        result,
                                        url,
                                        r1.headers,
                                        m.prettify(),
                                        m["action"]
                                    )
                            except KeyError:
                                query, token = Entropy(
                                    result, url, r1.headers, m.prettify(), m["action"]
                                )

                            # Check if the token is string-encoded
                            fnd, detct = Encoding(token)
                            if fnd == 0x01 and detct:
                                VulnLogger(
                                    url,
                                    "Token is a string encoded value which can be probably decrypted.",
                                    "[i] Encoding: " + detct,
                                )
                            else:
                                NovulLogger(
                                    url,
                                    "Anti-CSRF token is not a string encoded value.",
                                )

                            # Attempt tampering if we have a token
                            txor = False
                            if query and token:
                                txor = Tamper(url, action, result, r2.text, query, token)

                            # Now user #3
                            r3_resp = Get(url)
                            if r3_resp:
                                o2 = r3_resp.text
                            else:
                                o2 = ""

                            try:
                                form2 = Debugger.getAllForms(BeautifulSoup(o2, "html.parser"))[i]
                            except IndexError:
                                verbout(colors.R, "Form Index Error")
                                ErrorLogger(url, "Form Index Error.")
                                continue

                            verbout(colors.GR, "Preparing form inputs (3rd user)...")
                            contents2, genpoc = form.prepareFormInputs(form2)
                            r3 = Post(url, action, contents2)

                            # If we do not see a token or tamper indicates vulnerability,
                            # check for POST-based forgery
                            if POST_BASED and ((not query) or txor):
                                try:
                                    if "name" in m.attrs:
                                        PostBased(
                                            url,
                                            r1.text,
                                            r2.text,
                                            r3.text,
                                            action,
                                            result,
                                            genpoc,
                                            m.prettify(),
                                            m["name"],
                                        )
                                    else:
                                        PostBased(
                                            url,
                                            r1.text,
                                            r2.text,
                                            r3.text,
                                            action,
                                            result,
                                            genpoc,
                                            m.prettify(),
                                        )
                                except KeyError:
                                    PostBased(
                                        url,
                                        r1.text,
                                        r2.text,
                                        r3.text,
                                        action,
                                        result,
                                        genpoc,
                                        m.prettify(),
                                    )
                            else:
                                print(
                                    f"{colors.GREEN} [+] The form was requested with a Anti-CSRF token."
                                )
                                print(
                                    f"{colors.GREEN} [+] Endpoint {colors.BG}NOT VULNERABLE{colors.END}{colors.GREEN} to POST-Based CSRF Attacks!"
                                )
                                NovulLogger(url, "Not vulnerable to POST-Based CSRF Attacks.")

                        except HTTPError as msg:
                            verbout(colors.R, "Exception : " + msg.__str__())
                            ErrorLogger(url, msg)

                    action_done.append(action)
                    i += 1

        else:
            # If we are crawling
            verbout(colors.GR, "Initializing crawling and scanning...")
            crawler = Crawler.Handler(web, resp1)
            while crawler.noinit():
                url = next(crawler)
                print(f"{colors.C}Testing :> {colors.CYAN}{url}")

                try:
                    soup = crawler.process(fld)
                    if not soup:
                        continue

                    i = 0
                    if REFERER_ORIGIN_CHECKS:
                        verbout(
                            colors.O,
                            f"Checking endpoint request validation via {colors.GREY}Referer{colors.END}..."
                        )
                        if Referer(url):
                            ref_detect = 0x01

                        verbout(colors.O, "Confirming the vulnerability...")

                        verbout(
                            colors.O,
                            f"Confirming endpoint request validation via {colors.GREY}Origin{colors.END}..."
                        )
                        if Origin(url):
                            ori_detect = 0x01

                    verbout(
                        colors.O,
                        f"Retrieving all forms on {colors.GREY}{url}{colors.END}..."
                    )

                    for m in Debugger.getAllForms(soup):
                        FORMS_TESTED.append(f"(i) {url}:\n\n{m.prettify()}\n")

                        if not m.get("action"):
                            m["action"] = "/" + url.rsplit("/", 1)[1]
                            ErrorLogger(url, 'No standard "action" attribute.')

                        action = Parser.buildAction(url, m["action"])
                        if action and action not in action_done:
                            if FORM_SUBMISSION:
                                try:
                                    # user #1
                                    result, genpoc = form.prepareFormInputs(m)
                                    r1 = Post(url, action, result)

                                    # user #2
                                    result, genpoc = form.prepareFormInputs(m)
                                    r2 = Post(url, action, result)

                                    if COOKIE_BASED:
                                        Cookie(url, r1)

                                    try:
                                        if "name" in m.attrs:
                                            query, token = Entropy(
                                                result,
                                                url,
                                                r1.headers,
                                                m.prettify(),
                                                m["action"],
                                                m["name"],
                                            )
                                        else:
                                            query, token = Entropy(
                                                result,
                                                url,
                                                r1.headers,
                                                m.prettify(),
                                                m["action"]
                                            )
                                    except KeyError:
                                        query, token = Entropy(
                                            result,
                                            url,
                                            r1.headers,
                                            m.prettify(),
                                            m["action"],
                                        )
                                        ErrorLogger(url, 'No standard form "name".')

                                    fnd, detct = Encoding(token)
                                    if fnd == 0x01 and detct:
                                        VulnLogger(
                                            url,
                                            "String encoded token value. Token might be decrypted.",
                                            "[i] Encoding: " + detct,
                                        )
                                    else:
                                        NovulLogger(
                                            url,
                                            "Anti-CSRF token is not a string encoded value.",
                                        )

                                    txor = False
                                    if query and token:
                                        txor = Tamper(
                                            url, action, result, r2.text, query, token
                                        )

                                    o2_resp = Get(url)
                                    if o2_resp:
                                        o2 = o2_resp.text
                                    else:
                                        o2 = ""

                                    try:
                                        form2 = Debugger.getAllForms(BeautifulSoup(o2, "html.parser"))[i]
                                    except IndexError:
                                        verbout(colors.R, "Form Index Error")
                                        ErrorLogger(url, "Form Index Error.")
                                        continue

                                    verbout(colors.GR, "Preparing form inputs (3rd user)...")
                                    contents2, genpoc = form.prepareFormInputs(form2)
                                    r3 = Post(url, action, contents2)

                                    if POST_BASED and ((not query) or txor):
                                        try:
                                            if "name" in m.attrs:
                                                PostBased(
                                                    url,
                                                    r1.text,
                                                    r2.text,
                                                    r3.text,
                                                    m["action"],
                                                    result,
                                                    genpoc,
                                                    m.prettify(),
                                                    m["name"],
                                                )
                                            else:
                                                PostBased(
                                                    url,
                                                    r1.text,
                                                    r2.text,
                                                    r3.text,
                                                    m["action"],
                                                    result,
                                                    genpoc,
                                                    m.prettify(),
                                                )
                                        except KeyError:
                                            PostBased(
                                                url,
                                                r1.text,
                                                r2.text,
                                                r3.text,
                                                m["action"],
                                                result,
                                                genpoc,
                                                m.prettify(),
                                            )
                                    else:
                                        print(
                                            f"{colors.GREEN} [+] The form was requested with a Anti-CSRF token."
                                        )
                                        print(
                                            f"{colors.GREEN} [+] Endpoint {colors.BG}NOT VULNERABLE{colors.END}{colors.GREEN} to POST-Based CSRF Attacks!"
                                        )
                                        NovulLogger(
                                            url, "Not vulnerable to POST-Based CSRF Attacks."
                                        )

                                except HTTPError as msg:
                                    verbout(colors.RED, " [-] Exception : " + msg.__str__())
                                    ErrorLogger(url, msg)

                            action_done.append(action)
                            i += 1

                except HTTPError as e:
                    if str(e.code) == "403":
                        verbout(colors.R, "HTTP Authentication Error!")
                        verbout(colors.R, "Error Code : " + colors.O + str(e.code))
                        ErrorLogger(url, e)
                        quit()
                except URLError as e:
                    verbout(colors.R, "Exception at : " + url)
                    time.sleep(0.4)
                    verbout(colors.O, "Moving on...")
                    ErrorLogger(url, e)
                    continue

        # Final logging
        GetLogger()
        print(f"\n{colors.G}Scan done\n")
        Analysis()

    except KeyboardInterrupt:
        verbout(colors.R, "User Interrupt!")
        time.sleep(1.5)
        Analysis()
        print(colors.R + "Aborted!")
        ErrorLogger("KeyBoard Interrupt", "Aborted")
        GetLogger()
        sys.exit(1)
    except Exception as e:
        print("\n" + colors.R + "Encountered an error.\n")
        print(colors.R + "Please view the error log files to see what went wrong.")
        verbout(colors.R, e.__str__())
        ErrorLogger(url, e)
        GetLogger()
