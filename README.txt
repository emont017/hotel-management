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

RUNNING THE PROJECT

Once you've completed the setup, you can view the project in your web browser.

Open your web browser.

Go to the following address:
http://localhost/hotel-management/

The hotel management system homepage should now be live.

Default Admin Login:

Username: Test2

Password: Test123

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