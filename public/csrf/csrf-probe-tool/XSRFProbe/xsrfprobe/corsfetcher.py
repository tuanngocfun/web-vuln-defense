#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Module: corsfetcher.py

Purpose:
    This module fetches session cookies and a CSRF token from a client page via CORS.
    It supports two modes:
      1. A simple GET request to the client URL.
      2. A login simulation by sending a POST request to a login endpoint (assumed to be /auth/login)
         using dummy credentials (which you can adjust in configuration).
    The module then attempts to extract cookies and a CSRF token (first trying JSON, then HTML).

Usage:
    Import and call fetch_client_info(client_url) from your main engine.
    
Example:
    from xsrfprobe.xsrfprobe.corsfetcher import fetch_client_info
    cookies, csrf_token = fetch_client_info("http://localhost:9000/cors/index.html")
"""

import json
import re
import requests
from bs4 import BeautifulSoup
from xsrfprobe.core.verbout import verbout
from xsrfprobe.core.colors import color

# Create a colors instance for logging
colors = color()

def fetch_client_info(client_url):
    """
    Fetches session cookies and CSRF token from the client page.

    It first checks if the configuration (in xsrfprobe/files/config.py)
    provides a LOGIN_PAYLOAD dictionary. If so, it performs a POST to the
    login endpoint (constructed from SITE_URL + '/auth/login') using those credentials.
    Otherwise, it performs a simple GET request on the client URL.

    Extraction steps:
      1. Extract cookies from the response.
      2. If the response is JSON (Content-Type contains 'application/json'),
         attempt to extract a CSRF token from a field named "csrf".
      3. If no token is found via JSON, parse the HTML:
            - Look for input fields whose id or name contains keywords such as
              "csrf", "xsrf", or "token".
            - If still not found, look into meta tags with similar naming.
            - Optionally, search in script tags using a basic regex.
    
    Parameters:
      client_url (str): The URL of the client page (or login page) to fetch data from.
    
    Returns:
      tuple: (cookies_dict, csrf_token)
             where cookies_dict is a dictionary of cookies and csrf_token is
             the extracted token (or None if not found).
    """
    from xsrfprobe.files import config

    # Determine whether to simulate a login
    login_payload = getattr(config, "LOGIN_PAYLOAD", None)
    if login_payload and config.SITE_URL:
        # Construct the login URL (e.g., SITE_URL + '/auth/login')
        login_url = f"{config.SITE_URL.rstrip('/')}/auth/login"
        verbout(colors.GR, f"Performing login to fetch session info from: {login_url}")
        try:
            # IMPORTANT CHANGE: If your server expects JSON,
            # use `json=login_payload` and set 'Content-Type': 'application/json'.
            # If your server expects form data, use data=login_payload instead.
            headers = {
                "User-Agent": "XSRFProbe/4.4.29",
                "Content-Type": "application/json"
            }
            response = requests.post(
                login_url,
                json=login_payload,  # or data=login_payload if your server needs form data
                headers=headers,
                timeout=10,
                verify=False
            )
            response.raise_for_status()
        except requests.RequestException as e:
            verbout(colors.R, f"Login request failed: {e}")
            return {}, None
    else:
        # Otherwise, do a simple GET on the provided client URL
        verbout(colors.GR, f"Performing GET on client URL: {client_url}")
        try:
            response = requests.get(client_url, timeout=10, verify=False)
            response.raise_for_status()
        except requests.RequestException as e:
            verbout(colors.R, f"GET request failed: {e}")
            return {}, None

    # Extract cookies from the response.
    cookies = response.cookies.get_dict()
    if cookies:
        verbout(colors.G, f"Fetched cookies: {cookies}")
    else:
        verbout(colors.O, "No cookies found in the response.")

    csrf_token = None
    content_type = response.headers.get('Content-Type', '')

    # Try to extract token from a JSON response first.
    if 'application/json' in content_type.lower():
        verbout(colors.GR, "Detected JSON response.")
        try:
            data = response.json()
            # If your server returns { "csrf": "<token>" } then do:
            csrf_token = data.get('csrf')
            if csrf_token:
                verbout(colors.G, f"Extracted CSRF token from JSON: {csrf_token}")
            else:
                verbout(colors.O, "No 'csrf' field found in JSON response.")
        except json.JSONDecodeError as e:
            verbout(colors.R, f"JSON decoding failed: {e}")

    # If no token found, parse HTML.
    if not csrf_token:
        verbout(colors.GR, "Parsing HTML to extract CSRF token...")
        soup = BeautifulSoup(response.text, 'html.parser')

        # Define common selectors for CSRF token in input fields.
        import re
        selectors = [
            {'id': re.compile(r'csrf', re.IGNORECASE)},
            {'name': re.compile(r'csrf', re.IGNORECASE)},
            {'id': re.compile(r'xsrf', re.IGNORECASE)},
            {'name': re.compile(r'xsrf', re.IGNORECASE)},
            {'id': re.compile(r'token', re.IGNORECASE)},
            {'name': re.compile(r'token', re.IGNORECASE)},
        ]
        for sel in selectors:
            input_elem = soup.find('input', attrs=sel)
            if input_elem and input_elem.has_attr('value'):
                candidate = input_elem['value'].strip()
                if candidate:
                    csrf_token = candidate
                    verbout(colors.G, f"Found CSRF token in input {sel}: {csrf_token}")
                    break

        # If still not found, check meta tags.
        if not csrf_token:
            meta_tags = soup.find_all('meta', attrs={
                'name': re.compile(r'(csrf|xsrf)', re.IGNORECASE)
            })
            for meta in meta_tags:
                if meta.has_attr('content'):
                    candidate = meta['content'].strip()
                    if candidate:
                        csrf_token = candidate
                        verbout(colors.G, f"Found CSRF token in meta tag: {csrf_token}")
                        break

        # Optionally, try scanning script tags using a regex.
        if not csrf_token:
            verbout(colors.GR, "Scanning script tags for CSRF token...")
            csrf_regex = re.compile(r'(?:csrf|xsrf)[-_]?token["\']?\s*[:=]\s*["\']([\w\-]+)["\']', re.IGNORECASE)
            scripts = soup.find_all('script')
            for script in scripts:
                if script.string:
                    match = csrf_regex.search(script.string)
                    if match:
                        candidate = match.group(1).strip()
                        if candidate:
                            csrf_token = candidate
                            verbout(colors.G, f"Found CSRF token in script tag: {csrf_token}")
                            break

    return cookies, csrf_token
