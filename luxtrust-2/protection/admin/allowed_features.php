<?php
// Central feature whitelist for dashboard toggles.
// Keeps arbitrary POST names from creating random settings rows.
$ALLOWED_FEATURE_TOGGLES = [
    'blocked_user_agents',
    'static_range_blocking',
    'asn_blocking_enabled',
    'captcha_enabled',
    'math_captcha_enabled',
    'behavioral_analysis_enabled',
    'session_analysis_enabled',
    'request_pattern_analysis_enabled',
    'browser_fingerprinting_enabled',
    'tls_fingerprinting_enabled',
    'device_fingerprinting_enabled',
    'rate_limiting_enabled',
    'honeypot_enabled',
    'ip_reputation_enabled',

    // Professional safe upgrade toggles
    'adaptive_risk_engine_enabled',
    'temporary_block_ttl_enabled',
    'route_rate_limits_enabled',
    'advanced_cache_enabled',


    // Bot category and custom pattern controls
    'allow_search_engines',
    'allow_social_crawlers',
    'block_ai_crawlers',
    'block_security_scanners',
    'block_scraping_tools',
    'custom_ua_whitelist_enabled',
    'custom_ua_blocklist_enabled',

    // Local reputation list controls
    'block_spamhaus_drop_enabled',
    'block_tor_exit_nodes_enabled',

    // Policy manager controls
    'country_risk_scoring_enabled',
    'asn_rule_manager_enabled',

];
