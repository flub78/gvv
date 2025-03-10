git pull
bin/git_version.sh
echo "" >> installed.txt
git log --stat -n 1 >> installed.txt
cat installed.txt

