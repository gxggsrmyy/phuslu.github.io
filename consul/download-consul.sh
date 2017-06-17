#!/bin/bash

set -xe

export CONSUL_VERSION=0.8.4
export CONSUL_TEAMLATE_VERSION=0.18.5
export CONSUL_CLI_VERSION=0.3.1
export CONSUL_DOWNLOAD_URL=https://releases.hashicorp.com/consul/${CONSUL_VERSION}/consul_${CONSUL_VERSION}_linux_amd64.zip
export CONSUL_TEAMLATE_DOWNLOAD_URL=https://releases.hashicorp.com/consul-template/${CONSUL_TEAMLATE_VERSION}/consul-template_${CONSUL_TEAMLATE_VERSION}_linux_amd64.zip
export CONSUL_CLI_DOWNLOAD_URL=https://github.com/mantl/consul-cli/releases/download/v${CONSUL_CLI_VERSION}/consul-cli_${CONSUL_CLI_VERSION}_linux_amd64.tar.gz

if [ "$(uname -s)/$(uname -m)" != "Linux/x86_64" ]; then
	echo "Only support Linux/x86_64 platform, abort."
	exit
fi

for CMD in curl zip
do
	if ! command -v ${CMD}; then
		echo -e "tool ${CMD} is not installed, abort."
		exit 1
	fi
done

function download_consul() {
	curl -LOJ ${CONSUL_DOWNLOAD_URL}
	unzip $(basename ${CONSUL_DOWNLOAD_URL})
	rm -rf $(basename ${CONSUL_DOWNLOAD_URL})
}

function download_consul_template() {
	curl -LOJ ${CONSUL_TEAMLATE_DOWNLOAD_URL}
	unzip $(basename ${CONSUL_TEAMLATE_DOWNLOAD_URL})
	rm -rf $(basename ${CONSUL_TEAMLATE_DOWNLOAD_URL})
}

function download_consul_cli() {
	curl -LOJ ${CONSUL_CLI_DOWNLOAD_URL}
	tar xvf $(basename ${CONSUL_CLI_DOWNLOAD_URL})
	rm -rf $(basename ${CONSUL_CLI_DOWNLOAD_URL})
}

download_consul
download_consul_template
download_consul_cli
