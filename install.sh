#!/bin/bash

Print_Sucess_Info()
{
    echo "+------------------------------------------------------------------------+"
    echo "|          Shop123 for ${DISTRO} Linux Server                   |"
    echo "+------------------------------------------------------------------------+"
    echo "|         更多信息请访问: http://club.shop123.com                             |"
    echo "+------------------------------------------------------------------------+"
    echo "|  安装目录: ${VHOST_PATH}                                                  "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站域名: ${SHOP_DOMAIN}                                                 "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站数据库服务器: ${SHOP_DB_HOST}                                           "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站数据库名: ${SHOP_DB_NAME}                                             "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站数据库用户名: ${SHOP_DB_USER}                                           "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站数据库密码: ${SHOP_DB_PASS}                                            "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站时区: ${SHOP_TIMEZONE}                                               "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站语言: ${SHOP_LANGUAGE}                                               "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站货币单位: ${SHOP_CURRENCY}                                            "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站后台管理路径: ${SHOP_DOMAIN}/admin                                     "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站后台管理员帐号: ${SHOP_ADMIN_ACCOUNT}                                   "
    echo "+------------------------------------------------------------------------+"
    echo "|  网站后台管理员密码: ${SHOP_ADMIN_PASS}                     			       "
    echo "+------------------------------------------------------------------------+"
}

read -p "请输入你要绑定的域名(默认是:localhost):" SHOP_DOMAIN
SHOP_DOMAIN="${SHOP_DOMAIN:=localhost}"
echo "==========================="
echo "域名将绑定到:${SHOP_DOMAIN}"
echo "==========================="

read -p "请输入数据库主机名(一般是:localhost):" SHOP_DB_HOST
SHOP_DB_HOST="${SHOP_DB_HOST:=localhost}"
echo "==========================="
echo "数据库主机名为:${SHOP_DB_HOST}"
echo "==========================="

read -p "请输入您已创建的数据库名:" SHOP_DB_NAME
SHOP_DB_NAME="${SHOP_DB_NAME:=shop123}"
echo "数据库名为：${SHOP_DB_NAME}"
echo "==========================="

read -p "请输入这个数据库的用户名:" SHOP_DB_USER
SHOP_DB_USER="${SHOP_DB_USER:=shop123}"
echo "数据库用户名为:${SHOP_DB_USER}"
echo "==========================="

read -p "请输入这个数据库的密码:" SHOP_DB_PASS
SHOP_DB_PASS="${SHOP_DB_PASS:=shop123}"
echo "数据库密码为:${SHOP_DB_PASS}"
echo "==========================="

echo "请选择时区设定"
echo "1: Asia/Shanghai"
echo "2: America/Chicago"
read -p "请输入选项(默认是:1):" Shop123SelectTimezone
Shop123SelectTimezone="${Shop123SelectTimezone:=1}"
case "${Shop123SelectTimezone}" in
    1)
        SHOP_TIMEZONE="Asia/Shanghai"
        ;;
    2)
        SHOP_TIMEZONE="America/Chicago"
        ;;
    *)
        echo "没有输入，默认时区为:Asia/Shanghai"
        SHOP_TIMEZONE="Asia/Shanghai"
esac

echo "请选择语言"
echo "1: 简体中文"
echo "2: English"
read -p "请输入选项(默认是:1):" Shop123Selectlanguage
Shop123Selectlanguage="${Shop123Selectlanguage:=1}"
case "${Shop123Selectlanguage}" in
    1)
        SHOP_LANGUAGE="zh_Hans_CN"
        ;;
    2)
        SHOP_LANGUAGE="en_US"
        ;;
    *)
        echo "没有输入，默认语言为:简体中文"
        SHOP_LANGUAGE="zh_Hans_CN"
esac

echo "==========================="

echo "请选择货币单位"
echo "1: CNY"
echo "2: USD"
read -p "请输入选项(默认是:1):" Shop123SelectCurrency
Shop123SelectCurrency="${Shop123SelectCurrency:=1}"
case "${Shop123SelectCurrency}" in
    1)
        SHOP_CURRENCY="CNY"
        ;;
    2)
        SHOP_CURRENCY="USD"
        ;;
    *)
        echo "没有输入，默认货币为:CNY"
        SHOP_CURRENCY="CNY"
esac

read -p "请输入后台管理员帐号(默认是:shop123):" SHOP_ADMIN_ACCOUNT
SHOP_ADMIN_ACCOUNT="${SHOP_ADMIN_ACCOUNT:=shop123}"
echo "==========================="
echo "后台管理员帐号为:${SHOP_ADMIN_ACCOUNT}"
echo "==========================="

read -p "请输入后台管理员密码 (默认是:shop123):" SHOP_ADMIN_PASS
SHOP_ADMIN_PASS="${SHOP_ADMIN_PASS:=shop123}"
echo "==========================="
echo "后台管理员密码为:${SHOP_ADMIN_PASS}"
echo "==========================="

echo ""
echo "按任意键开始安装或者按Ctrl+C取消安装"
OLDCONFIG=`stty -g`
stty -icanon -echo min 1 time 0
dd count=1 2>/dev/null
stty ${OLDCONFIG}

echo "开始安装shop123数据库..."
./bin/shop123 setup:install --base-url=http://${SHOP_DOMAIN}/ \
	         --db-host=${SHOP_DB_HOST} --db-name=${SHOP_DB_NAME} --db-user=${SHOP_DB_USER} --db-password=${SHOP_DB_PASS} \
	         --admin-firstname=Shop123 --admin-lastname=User --admin-email=user@example.com \
	         --admin-user=${SHOP_ADMIN_ACCOUNT} --admin-password=${SHOP_ADMIN_PASS} --language=${SHOP_LANGUAGE} \
	         --currency=${SHOP_CURRENCY} --timezone=${SHOP_TIMEZONE} --use-rewrites=1
	         
./bin/shop123 module:disable Magento_CheckoutAgreements Magento_TaxImportExport

Print_Sucess_Info
