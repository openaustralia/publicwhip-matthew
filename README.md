> [!WARNING]
> This project has been archived because we are not actively maintaining nor using it. It hasn't seen activity since April 2013 and may have known security vulnerabilities that have not been addressed. If you would like to move this work forward, please do! If we get enough interest, we might reconsider working on this again. You can always donate to provide us with more resources.

Note: I (@benrfairless) used AI to assist with the process of archiving this repository, however I reviewed the decision myself before taking action.
The Public Whip Source Code
---------------------------

Hello!  Here's the source code behind the Public Whip website.  To see the end
product go to http://www.publicwhip.org.uk.  If you don't know what this is all
about, have a look at the FAQ there.  

To learn how to use the code look at http://www.publicwhip.org.uk/project/code.php 
or locally in webpage/project/code.php.  You should also check out the
Parliament Parse project at http://ukparse.kforge.net/parlparse, which is the
scraper that made the data Public Whip uses.

A description of the files and folders in this package follows.

LICENSE.html - Details of open source licensing terms, under the Affero GNU GPL
todo.txt - Things I'm thinking of doing in the short term
ideas.txt - Zillions of ideas of things which could be done
errata.txt - Errors in Hansard that the software has found

loader    - Load XML files from ukparse into the database
website   - Code for www.publicwhip.org.uk, PHP extracts data from database/XML
build     - Scripts I use for admin, such as to upload to www.publicwhip.org.uk
custom    - Various one off scripts and graphics made for special purposes
artwork   - High resolution graphics relating to Public Whip

If you need any help, please email me francis@flourish.org.