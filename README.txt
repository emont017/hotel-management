PREREQUISITES

Before you begin, make sure you have the following software installed:

XAMPP: You can download it from the official Apache Friends website (https://www.apachefriends.org/index.html).



INSTALLATION AND SETUP

Follow these steps carefully to set up the project.

Step 1: Download the Project Files from GitHub

First, you need to get a copy of the project on your computer.

Open your computer's Terminal (on Mac) or Git Bash (on Windows).

Navigate to a folder where you want to save the project (like your Desktop or Documents).

Clone the repository by running this command:


git clone https://github.com/emont017/hotel-management.git


This will create a new folder named hotel-management with all the project files inside.



Step 2: Start Your Local Server with XAMPP

Open the XAMPP Control Panel.

Click the "Start" button next to both Apache and MySQL.

Both should turn green, indicating they are running correctly.


Step 3: Set Up the Database with phpMyAdmin

This is the most important step. The website will not work without the database.

Open phpMyAdmin: Open your web browser and go to this address: http://localhost/phpmyadmin

Create the Database:

On the left sidebar, click on "New".

In the "Database name" field, type exactly hotel_management.

Click the "Create" button.

Import the Database Structure:

In the left sidebar, click on the hotel_management database you just created.

Click on the "Import" tab at the top of the page.

Under the "File to import" section, click the "Choose File" button.

Navigate to the project folder you downloaded from GitHub (hotel-management). Inside that folder, go into the db_backup folder and select the .sql file.

Scroll to the bottom of the page and click the "Go" button.

If it's successful, you will see a green message and your new tables will appear in the sidebar under hotel_management.


Step 4: Move the Project into Your Web Server Folder

Find the hotel-management project folder that you downloaded.

Move this entire folder into the htdocs folder inside your XAMPP installation directory.

On Windows, this is usually C:\xampp\htdocs\

On macOS, this is usually /Applications/XAMPP/xamppfiles/htdocs/


Step 5: Configure the Local Domain (http://hotel.local)- Instructions for Mac & Windows below:

Configure Apache (Mac)

1. In the XAMPP Control Panel, Stop the Apache service if it is running.
2. Use Finder to go to your Applications folder, open XAMPP, and find the file at the following path:

/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf

3. Add this code block to the very end of the file:

<VirtualHost *:80>
    DocumentRoot "/Applications/XAMPP/xamppfiles/htdocs/hotel-management/public"
    ServerName hotel.local
    <Directory "/Applications/XAMPP/xamppfiles/htdocs/hotel-management/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

4. Save and close the file.

Configure Mac Hosts file

1. Open Terminal
2. Open hosts file with the following command:

sudo nano /etc/hosts

3. Add the following line at the bottom of the file:

    127.0.0.1    hotel.local

4. Save and exit.

Configure Apache conf file

1. Open the main Apache configuration file, httpd.conf:

/Applications/XAMPP/xamppfiles/etc/httpd.conf

2. Find the following line in the file:

#Include etc/extra/httpd-vhosts.conf

3. Remove the # symbol from the beginning of that line. It should now look like this:

Include etc/extra/httpd-vhosts.conf

4. Save and close the httpd.conf file.


Restart Apache

1. Go back to the XAMPP application.
2. Restart the Apache server.
3. You can now access the entire project by navigating to http://hotel.local/


--------------------------------------------------------------------------------


Configure Apache (Windows)

1.  In the XAMPP Control Panel, Stop the Apache service if it is running.
2.  Open the following file in a text editor: C:\xampp\apache\conf\extra\httpd-vhosts.conf
3.  Add this code block to the very end of the file:

    <VirtualHost *:80>
        DocumentRoot "C:/xampp/htdocs/hotel-management/public"
        ServerName hotel.local
        <Directory "C:/xampp/htdocs/hotel-management/public">
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>


4.  Save and close the file.


Configure Windows `hosts` File

1.  Open Notepad as an Administrator
2.  In Notepad, go to File > Open and navigate to this exact path: 

C:\Windows\System32\drivers\etc\hosts

3.  Go to the last line of the file and add this new line:

    127.0.0.1    hotel.local

4.  Save and close the file.


Restart Apache

1.  Go back to the XAMPP Control Panel and Start the Apache service.
2.  You can now access the entire project by navigating to http://hotel.local/


Default Admin Login:

User: Test2
Psswd: Test123


HOW TO SAVE AND PUSH YOUR WORK TO GITHUB

When you make changes to the code, you need to save them back to GitHub so the rest of the team can see them.

Step 1: Add and Commit Your Changes

After you've saved your changes in your code editor, open your Terminal or Git Bash.

Navigate into the project folder:
cd /path/to/your/htdocs/hotel-management

Add all the changed files to be saved:
git add .

Commit (save) the changes with a short, descriptive message:
git commit -m "Describe the change you made"

Step 2: Push Your Changes to GitHub

Run the push command:
git push

Authentication: GitHub no longer accepts your regular password here. You must use a Personal Access Token (PAT).

You will be prompted for your username and password.

Username: Enter your GitHub username.

Password: PASTE your Personal Access Token here. Do not type your GitHub password.

If you don't have a token, you can generate one in your GitHub Developer Settings (https://github.com/settings/tokens/new). Make sure to give it the "repo" scope.


--Version 1.2 updates--

Review configuration setup information as the project was overhauled to run on a local domain.

Professional File Structure: Reorganized all files into a secure structure with a /public web root and dedicated folders for config, includes, and PHP logic.

Local Domain Setup: Configured the Apache server and Windows hosts file to run the project on a  local domain (http://hotel.local).

Centralized Styling: Removed all inline styles and <style> blocks from PHP files and consolidated them into a single style.css file.

Consistent Theme: Established a consistent color scheme based on FIU's blue and gold.

Admin Dashboard Redesign: Revamped the admin dashboard into a KPI-driven layout with cards for key metrics like ADR and RevPAR.

Interactive Housekeeping Page: Transformed the housekeeping list into a interactive table with filter tabs and inline actions.

Dynamic Booking Page: Upgraded the "Book a Stay" page into a multi-step process that dynamically checks for room availability.

Compact Footer: Redesigned the footer to be more compact.

User Editing: Added a feature for admins to edit all user details, including their role and password.

Dynamic Pricing: Implemented a room_rates table to allow for dynamic pricing based on dates, replacing the old price system.

Guest Billing Foundation: Added folios and folio_items tables to the database.

Fixed all PHP errors, warnings, and notices.

Patched an SQL injection vulnerability and ensured all database queries are secure.

Corrected all broken links and form submission errors by implementing a secure API endpoint pattern.

Fixed the booking logic to correctly create a new guest profile for every public booking.

Configured and fixed the email sending functionality.
