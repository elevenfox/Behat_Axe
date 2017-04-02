#!/bin/bash

case=$1

IFS=$'\n'

echo ''
echo '### Start automate testing locally ###' | while read -r line; do echo "$(date '+%Y-%m-%d %T ') $line"; done
echo ''

if [ ! -f behat.yml ]; then
	echo "Cannot find behat.yml. Please copy behat-template.yml to behat.yml and edit it to fit your local env."
	echo ''
	exit 1;
fi

#To see if java runtime is installed
javaRuntime=0
javaVersion=`java -version 2>&1`
echo ''
for a in $javaVersion
do 
	if [[ $a == *"java version"* ]]; then
		javaRuntime=1
	fi
	if [[ $a == *"openjdk version"* ]]; then
		javaRuntime=1
	fi
done
if [ $javaRuntime -eq 0 ]; then
	echo " - No JAVA Runtime found, please install JAVA Runtime first. - " | while read -r line; do echo "$(date '+%Y-%m-%d %T ') $line"; done
	echo ''
	exit 1;
fi

# To see if selenium server is running
seleniumRunning=0
process=`ps -ef | grep selenium`
for b in $process
do
    if [[ $b == *"java -jar selenium-server-standalone-3.3.0.jar"* ]]; then
  		seleniumRunning=1
	fi
done

if [ $seleniumRunning -eq 1 ]; then
	echo "Selenium Server is running, will use it."  | while read -r line; do echo "$(date '+%Y-%m-%d %T ') $line"; done
	echo ''
else
	echo "No running Selenium Server, will start one..."  | while read -r line; do echo "$(date '+%Y-%m-%d %T ') $line"; done
	echo ''
	cd ./selenium
	rm -f chromedriver

	# What OS is it? Load correct chrome driver
	ext="mac"
	os=`uname`
	if [ $os = "Linux" ]; then
		bit=`uname -m`
		ext="linux.$bit"
	fi
	ln -s chromedriver.$ext chromedriver

	nohup java -jar selenium-server-standalone-3.3.0.jar 2>&1 >/dev/null &
	cd ../
fi

echo ''
./bin/behat --profile=local --format=pretty $case

echo ''
echo '### Automate testing finished ###' | while read -r line; do echo "$(date '+%Y-%m-%d %T ') $line"; done
