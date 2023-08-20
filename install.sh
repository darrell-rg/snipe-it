#use this to install on an fresh ubuntu 22.04 box
#wget https://raw.githubusercontent.com/darrell-rg/snipe-it/master/install.sh

# ensure running as root
if [ "$(id -u)" != "0" ]; then
    #Debian doesnt have sudo if root has a password.
    if ! hash sudo 2>/dev/null; then
        exec su -c "$0" "$@"
    else
        exec sudo "$0" "$@"
    fi
fi

wget https://raw.githubusercontent.com/darrell-rg/snipe-it/master/snipeit.sh
chmod 744 snipeit.sh
./snipeit.sh 2>&1 | tee -a /var/log/snipeit-install.log


#use certbot for letsencrypt
snap install --classic certbot
certbot --apache

#change the git origin 
cd /var/www/html/snipeit
git remote set-url origin git@github.com:darrell-rg/snipe-it.git
#actually, use the https so we do not have to put our private key on the server
git remote set-url origin https://github.com/darrell-rg/snipe-it.git