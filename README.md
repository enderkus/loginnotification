# Login Notification Module for WHMCS

<div align="center">

![Login Notification](https://img.shields.io/badge/WHMCS-Login%20Notification-blue)
![Version](https://img.shields.io/badge/Version-1.0%20BETA-orange)
![License](https://img.shields.io/badge/License-MIT-green)

</div>

## 📝 Description

Login Notification is a WHMCS addon module that sends email notifications to users when they log in to their account. The notification includes detailed information about the login attempt, such as IP address, location, and ISP details.

## ✨ Features

- 📧 Automatic email notifications on login
- 🌍 IP geolocation information
- 🏢 ISP detection
- 📊 Admin panel with login history
- 🔍 Advanced search functionality
- 📱 Responsive design
- 🔒 Session-based duplicate notification prevention

## 📋 Requirements

- WHMCS 8.x or higher
- PHP 7.4 or higher
- Active internet connection (for IP geolocation)

## 💾 Installation

1. Download the latest release
2. Upload the `loginnotification` folder to your WHMCS installation under:
   ```
   /modules/addons/
   ```
3. Go to WHMCS Admin Area → Setup → Addon Modules
4. Find "Login Notification" and click Activate
5. Configure access control for admin roles

## ⚙️ Configuration

The module comes with minimal configuration requirements:

1. **Enable/Disable**: Toggle the module on/off
2. **Access Control**: Set which admin roles can access the module

## 📊 Admin Panel Features

- View all login attempts
- Search by:
  - User name
  - Email address
  - IP address
  - Location
- Pagination system
- Status indicator
- Responsive table design

## 📧 Email Template

The module automatically creates an email template with the following merge fields:

- `{$client_name}` - Client's full name
- `{$login_time}` - Date and time of login
- `{$ip_address}` - Client's IP address
- `{$location}` - Geographic location
- `{$isp}` - Internet Service Provider
- `{$company_name}` - Your company name

You can customize this template under:
Setup → Email Templates → Login Notification Email

## 🔒 Security Features

- Session-based duplicate notification prevention
- SQL injection protection
- XSS prevention
- WHMCS security token validation
- Secure email template system

## 🛠️ Troubleshooting

1. **Emails not sending?**
   - Check WHMCS email settings
   - Verify SMTP configuration
   - Check spam filters

2. **Location not showing?**
   - Ensure internet connectivity
   - Check if ip-api.com is accessible
   - Verify IP address format

3. **Module not showing in admin area?**
   - Check admin role permissions
   - Verify module activation
   - Clear WHMCS cache

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

**Ender KUS**
- Email: ender@enderkus.com.tr
- Website: [enderkus.com.tr](https://enderkus.com.tr)

## 🤝 Support

For support, please create an issue in the GitHub repository or contact via email.

---

<div align="center">
Made with ❤️ by Ender KUS
</div>

---

## ⚠️ Beta Warning

> **IMPORTANT NOTICE**: This module is currently in BETA stage and is under active development. It may not be suitable for production environments. Use at your own risk and thoroughly test before deploying to production servers. Features may change, and updates may include breaking changes.
>
> Development is ongoing, and we appreciate any feedback or bug reports to improve the module.
  </rewritten_file> 