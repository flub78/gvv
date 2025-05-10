# Migrating Your Web Application from Ionos to Oracle Cloud Free Tier

I'll guide you through migrating your PHP web application from Ionos to Oracle Cloud Free Tier with Hestia Control Panel, ensuring other applications remain unaffected.

## Phase 1: Preparation

1. **Document your current setup**
   - Note your domain name and subdomain that hosts the application
   - Document PHP version requirements
   - List any databases and their credentials
   - Identify all website files, configurations, and dependencies

2. **Set up Oracle Cloud Free Tier account**
   - Sign up at https://www.oracle.com/cloud/free/
   - Create your account and verify your email/phone
   - Set up multi-factor authentication for security

3. **Backup your data from Ionos**
   - Download a complete backup of your website files
   - Export any databases (MySQL/MariaDB)
   - Download configuration files (.htaccess, php.ini)
   - Save SSL certificates if you have custom ones

## Phase 2: Set Up Oracle Cloud Infrastructure

1. **Create a virtual machine**
   - Log into Oracle Cloud console
   - Navigate to Compute → Instances → Create Instance
   - Select an "Always Free" eligible shape (typically AMD VM.Standard.E2.1.Micro)
   - Choose Ubuntu 20.04 or newer as OS
   - Create and download SSH keys
   - Configure networking with public IP
   - Launch instance

2. **Connect to your new VM**
   - SSH into your VM using the downloaded key:
     ```
     ssh -i /path/to/key.pem ubuntu@your-vm-ip
     ```
   - Update system:
     ```
     sudo apt update && sudo apt upgrade -y
     ```

3. **Install Hestia Control Panel**
   - Run the Hestia installer:
     ```
     wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
     ```
   - Make it executable:
     ```
     chmod +x hst-install.sh
     ```
   - Run the installer with required parameters:
     ```
     sudo ./hst-install.sh --apache yes --nginx yes --phpfpm yes --multiphp yes --vsftpd yes --proftpd no --named yes --mysql yes --postgresql no --exim yes --dovecot yes --sieve no --clamav yes --spamassassin yes --iptables yes --fail2ban yes --quota no --api yes --interactive yes
     ```
   - During installation, set admin credentials and your email

## Phase 3: Configure DNS and Domain

1. **Keep your domain at current registrar**
   - No need to transfer the domain

2. **Update DNS records**
   - Log in to your domain registrar or DNS provider
   - Create or update the subdomain A record to point to your Oracle VM IP
   - Example: `webappsubdomain.yourdomain.com. IN A your-oracle-vm-ip`
   - If using Cloudflare or similar, ensure proxy is off initially (DNS only)
   - Set low TTL values (300-900 seconds) to make changes propagate faster

3. **Wait for DNS propagation**
   - Use `dig webappsubdomain.yourdomain.com` or online tools to check propagation
   - This typically takes 15 minutes to 24 hours

## Phase 4: Set Up Your Website in Hestia

1. **Access Hestia Control Panel**
   - Open `https://your-oracle-vm-ip:8083` in your browser
   - Log in with admin credentials

2. **Create a new user account**
   - Navigate to "Users" → "Add User"
   - Create a separate user for your website management
   - Set password and other details

3. **Add your domain**
   - Go to "WEB" → "Add Web Domain"
   - Enter your subdomain (e.g., webappsubdomain.yourdomain.com)
   - Configure PHP version to match your requirements
   - Enable SSL with Let's Encrypt

4. **Upload your website files**
   - Use SFTP to upload your files:
     ```
     sftp -i /path/to/key.pem username@your-oracle-vm-ip
     ```
   - Or use FileZilla/WinSCP with your Hestia user
   - Upload files to `/home/username/web/webappsubdomain.yourdomain.com/public_html/`

5. **Import database**
   - Go to "DB" → "Add Database" in Hestia
   - Create a new database and user
   - Import your database backup:
     ```
     mysql -u database_user -p database_name < backup.sql
     ```

6. **Update configuration files**
   - Modify your application's database connection settings
   - Update any hardcoded URLs or paths
   - Configure .htaccess if needed

## Phase 5: Testing and Completion

1. **Test your website thoroughly**
   - Check for broken links or missing assets
   - Test all functionality
   - Verify database connectivity
   - Check for PHP errors

2. **Fine-tune performance**
   - Adjust PHP settings if needed
   - Set up caching if your application supports it
   - Configure firewall rules

3. **Complete the migration**
   - Once testing is successful, ensure DNS is fully propagated
   - Monitor the site for any issues

## Phase 6: Post-Migration Tasks

1. **Set up regular backups**
   - Configure Hestia's built-in backup system
   - Consider additional backup solutions

2. **Update security settings**
   - Change default SSH port
   - Configure fail2ban
   - Install and configure a firewall (UFW)

3. **Keep Ionos hosting active temporarily**
   - Don't cancel immediately; keep as backup for 1-2 weeks
   - Once confirmed everything works, you can safely cancel the PHP support or downgrade your plan

Let me know if you need more details on any of these steps, or if you have questions about specific aspects of this migration!