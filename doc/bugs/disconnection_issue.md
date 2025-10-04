# Disconnection Issue Analysis

## Problem Statement
Some users experience unwanted disconnections with no obvious pattern. The issue doesn't correlate with:
- Poor Internet connection
- Specific browsers
- Specific operating systems

Some users rarely or never experience the problem, while others experience it frequently.

**Expected behavior:** Users should stay connected until they logout or the 2-hour timeout expires.

## Analysis

After analyzing the GVV authentication and session management code, I've identified several potential causes for unexpected disconnections:

### 1. User Agent Matching (High Probability Cause)

**Location:** [system/libraries/Session.php:192-196](system/libraries/Session.php#L192-L196)

**Configuration:** [application/config/config.php:261](application/config/config.php#L261)
```php
$config['sess_match_useragent'] = TRUE;
```

**Issue:** The session system validates that the User Agent string matches on every request. If the User Agent changes during the session, the user is immediately disconnected.

**Why this causes sporadic disconnections:**
- Some browsers automatically update in the background and change their User Agent string
- Browser extensions can modify the User Agent
- Security/privacy software may rotate User Agents
- Mobile browsers switching between mobile/desktop modes
- Some anti-tracking features randomize User Agent strings

**Code snippet:**
```php
// Does the User Agent Match?
if ($this->sess_match_useragent == TRUE AND trim($session['user_agent']) != trim(substr($this->CI->input->user_agent(), 0, 120)))
{
    $this->sess_destroy();  // Session destroyed immediately
    return FALSE;
}
```

### 2. Session Update Race Condition (Medium Probability)

**Location:** [system/libraries/Session.php:343-389](system/libraries/Session.php#L343-L389)

**Configuration:**
```php
$config['sess_time_to_update'] = 300;  // 5 minutes
```

**Issue:** Every 5 minutes, the session ID is regenerated. The process involves:
1. Generating a new session ID
2. Updating the database with the new ID (using the old ID as the WHERE clause)
3. Setting a new cookie

**Race condition scenario:**
- User has multiple tabs open
- Tab A triggers session update at exactly 5:00
- Tab B makes a request at 5:00.1 seconds with the old session ID
- Tab A updates database with new session ID
- Tab B's request fails to find the session in database → disconnection

**Code snippet:**
```php
function sess_update()
{
    // We only update the session every five minutes by default
    if (($this->userdata['last_activity'] + $this->sess_time_to_update) >= $this->now)
    {
        return;
    }

    // ... generates new session ID ...

    // Updates database - potential race condition here
    $this->CI->db->query($this->CI->db->update_string($this->sess_table_name,
        array('last_activity' => $this->now, 'session_id' => $new_sessid),
        array('session_id' => $old_sessid)));
}
```

### 3. Session Expiration Check Timing (Low to Medium Probability)

**Location:** [system/libraries/Session.php:178-182](system/libraries/Session.php#L178-L182)

**Configuration:**
```php
$config['sess_expiration'] = 7200;  // 2 hours in seconds
```

**Issue:** The session expiration check uses strict comparison:
```php
if (($session['last_activity'] + $this->sess_expiration) < $this->now)
{
    $this->sess_destroy();
    return FALSE;
}
```

**Why this could cause issues:**
- If the server time jumps forward (NTP sync, daylight saving time, manual adjustment)
- If there's clock drift between application servers (if load-balanced)
- The `last_activity` is only updated every 5 minutes, not on every request

### 4. Database Session Lookup Failure (Low Probability)

**Location:** [system/libraries/Session.php:199-220](system/libraries/Session.php#L199-L220)

**Issue:** Sessions are stored in the database and validated on every request. Any database connectivity issue or timeout will cause disconnection.

**Code snippet:**
```php
if ($this->sess_use_database === TRUE)
{
    $query = $this->CI->db->get($this->sess_table_name);

    // No result? Kill it!
    if ($query->num_rows() == 0)
    {
        $this->sess_destroy();
        return FALSE;
    }
}
```

### 5. Cookie Path/Domain Issues (Low Probability)

**Configuration:** [application/config/config.php:274-276](application/config/config.php#L274-L276)
```php
$config['cookie_prefix']    = '';
$config['cookie_domain']    = '';
$config['cookie_path']      = '/';
```

**Issue:** If users access the application through different URLs (e.g., with/without www, different subdomains), cookies may not be sent, causing disconnection.

## Recommendations

### Immediate Actions (High Priority)

**1. Disable User Agent Matching**

Edit [application/config/config.php:261](application/config/config.php#L261):
```php
$config['sess_match_useragent'] = FALSE;  // Changed from TRUE
```

**Rationale:** This is the most likely cause of sporadic disconnections. While this slightly reduces security, the User Agent is easily spoofable anyway and provides minimal real security benefit. The improved user experience outweighs the minimal security reduction.

**Testing:** Deploy this change and monitor if disconnection reports decrease significantly.

### Medium-Term Actions (Medium Priority)

**2. Increase Session Update Interval**

Edit [application/config/config.php:262](application/config/config.php#L262):
```php
$config['sess_time_to_update'] = 600;  // Changed from 300 (10 minutes instead of 5)
```

**Rationale:** Reduces frequency of session ID regeneration, lowering the probability of race conditions when users have multiple tabs open. The security impact is minimal.

**3. Add Session Overlap Grace Period**

This requires code modification in [system/libraries/Session.php](system/libraries/Session.php). Consider implementing a grace period where both old and new session IDs are valid for a short time (30-60 seconds) during session regeneration. This would require custom development.

### Long-Term Actions (Low Priority but Recommended)

**4. Implement Session Logging**

Add logging around session destruction to identify patterns:
- Log User Agent changes
- Log session regeneration events
- Log database lookup failures
- Track time between disconnections per user

This will help identify which specific cause is affecting which users.

**5. Monitor Server Time Synchronization**

Ensure NTP is properly configured and check for time drift issues, especially if running multiple application servers.

**6. Database Connection Monitoring**

Monitor database query response times and connection stability, especially during reported disconnection times.

**7. Consider Alternative Session Storage**

For better reliability, consider:
- Redis or Memcached for session storage (faster, more reliable than database)
- Native PHP sessions (simpler, but requires sticky sessions if load-balanced)

## Testing Strategy

Since the issue is not easily reproducible:

1. **Deploy User Agent fix first** (lowest risk, highest probability fix)
2. **Monitor for 1-2 weeks** - ask affected users to report if issue persists
3. **If issue persists**, deploy session update interval increase
4. **Implement logging** to gather data on remaining disconnections
5. **Analyze logs** to identify remaining root causes

## Success Metrics

- Reduction in user-reported disconnection complaints
- Increased average session duration
- Reduced number of login events per user per day

## Risk Assessment

| Change | Risk Level | Impact if Wrong | Rollback Difficulty |
|--------|-----------|-----------------|---------------------|
| Disable User Agent matching | Low | Minimal security reduction | Easy (config change) |
| Increase update interval | Low | Minimal security reduction | Easy (config change) |
| Add session overlap | Medium | Potential security issues | Medium (code change) |

## References

- [CodeIgniter Session Library Documentation](https://www.codeigniter.com/userguide2/libraries/sessions.html)
- DX_Auth Library: [application/libraries/DX_Auth.php](application/libraries/DX_Auth.php)
- Session Configuration: [application/config/config.php:240-262](application/config/config.php#L240-L262)
- DX_Auth Configuration: [application/config/dx_auth.example.php](application/config/dx_auth.example.php)
