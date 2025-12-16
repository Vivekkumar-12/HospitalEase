# Deploying HospitalEase on Render

This guide will help you deploy the HospitalEase application on Render using Docker.

## Prerequisites

1. A Render account (Sign up at https://render.com)
2. Git repository with your code (GitHub, GitLab, or Bitbucket)
3. Your application code pushed to the repository

## Step-by-Step Deployment Guide

### 1. Update Database Configuration

Before deploying, you need to update the database configuration to use environment variables:

**Option A: Modify existing config.php**
Replace the content in `include/config.php` with:

```php
<?php
define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'myhmsdb');

$con = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
?>
```

### 2. Push Your Code to Git Repository

```bash
git init
git add .
git commit -m "Initial commit for Render deployment"
git branch -M main
git remote add origin <your-repository-url>
git push -u origin main
```

### 3. Create PostgreSQL Database on Render (Recommended)

The application is now configured to work with PostgreSQL:

1. Log in to your Render dashboard
2. Click "New +" and select "PostgreSQL"
3. Name your database: `hospitalease-db`
4. Choose a plan (Free tier available)
5. Click "Create Database"
6. Wait for the database to be created
7. Copy the **Internal Database URL** (found in the database settings)

**Note:** The code has been updated with a compatibility layer that allows PostgreSQL to work with the existing mysqli code!

### 4. Create Web Service on Render

1. Log in to Render Dashboard (https://dashboard.render.com)
2. Click "New +" and select "Web Service"
3. Connect your Git repository
4. Configure the service:
   - **Name:** `hospitalease`
   - **Environment:** `Docker`
   - **Branch:** `main`
   - **Dockerfile Path:** `./Dockerfile`
   - **Plan:** Select your preferred plan (Free tier available)

### 5. Configure Environment Variables

In the Render Web Service dashboard, add the following environment variable:

**For PostgreSQL (Recommended):**
- **DATABASE_URL:** Paste the Internal Database URL from your PostgreSQL service
  - Example: `postgres://user:password@hostname:5432/dbname`

**Alternative method (individual variables):**
- **DB_HOST:** Your PostgreSQL host
- **DB_USER:** Your PostgreSQL username
- **DB_PASS:** Your PostgreSQL password
- **DB_NAME:** `myhmsdb`
- **DB_PORT:** `5432`

**For Local MySQL Development:**
- **USE_MYSQL:** Set t to PostgreSQL

**Option A: Using Render Shell (Recommended)**
1. Go to your PostgreSQL service in Render dashboard
2. Click "Shell" tab
3. In the shell, run:
   ```bash
   psql $DATABASE_URL
   ```
4. Paste the contents of `myhmsdb_postgres.sql` or use:
   ```bash
   curl https://raw.githubusercontent.com/YOUR_USERNAME/HospitalEase/main/myhmsdb_postgres.sql | psql $DATABASE_URL
   ```

**Option B: Using Local psql Client**
1. Install PostgreSQL client on your machine
2. Run:
   ```bash
   psql "your-postgres-connection-url-here" < myhmsdb_postgres.sql
   ```

**Option C: Using pgAdmin or DBeaver**
1. Connect to your Render PostgreSQL database
2. Open and execute the `myhmsdb_postgres.sql` filebash
   psql <your-postgres-connection-url> < myhmsdb_postgres.sql
   ```

### 7. Deploy

1. Click "Create Web Service"
2. Render will automatically build and deploy your Docker container
3. Wait for the deployment to complete (usually 5-10 minutes)
4. Your app will be available at: `https://hospitalease.onrender.com` (or your custom domain)
PostgreSQL Compatibility Layer** - The application has been updated with:
   - Automatic PostgreSQL connection when deployed on Render
   - MySQL compatibility functions that work with PostgreSQL
   - Fallback to MySQL for local development (set `USE_MYSQL=true`
### Database Considerations

1. **Render doesn't provide managed MySQL** - You have two options:
   - Use an external MySQL provider (recommended for MySQL compatibility)
   - Convert to PostgreSQL (Render offers free PostgreSQL)

2. **Free Tier Limitations:**
   - Web services spin down after 15 minutes of inactivity
   - First request after spin-down may take 30-60 seconds
   - Database is limited to 1GB storage
   - 90 days of inactivity will result in service deletion

### File Uploads

If your application handles file uploads, note that Render's filesystem is ephemeral:
- Files uploaded to the container will be lost on restart
- Consider using cloud storage (AWS S3, Cloudinary, etc.) for persistent file storage

### Custom Domain (Optional)

1. In your web service settings, click "Settings"
2. Scroll to "Custom Domain"
3. Add your domain and configure DNS records as instructed

### Monitoring and Logs

- View logs in the Render dashboard under your service's "Logs" tab
- Set up alerting in the "Settings" tab

## Troubleshooting

### Database Connection Issues
- Verify environment variables are set correctly
- Check database credentials and host
- Ensure database is accessible from Render's IP range

### Application Errors
- Check logs: `Render Dashboard > Your Service > Logs`
- Verify all PHP extensions are installed in Dockerfile
- THow It Works: PostgreSQL with mysqli Code

The updated `config.php` includes:
- **Automatic detection** of PostgreSQL vs MySQL
- **Wrapper classes** that provide mysqli-like interface for PDO/PostgreSQL
- **Full compatibility** with existing mysqli code (no changes needed in other files)
- **Local development** still works with MySQL (set `USE_MYSQL=true` environment variable)
## Alternative: Using Railway (MySQL Support)

If you prefer to keep MySQL, consider Railway.app which offers managed MySQL:
1. Similar deployment process
2. Native MySQL support
3. Free tier available

## Support

For issues:
- Render Documentation: https://render.com/docs
- Render Community: https://community.render.com

---

**Security Reminder:** Never commit sensitive credentials to Git. Always use environment variables for passwords and API keys.
