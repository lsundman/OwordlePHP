
ENV ?= http://localhost:8000/bot.php

domain := $(shell python3 -c \
	'from urllib.parse import urlsplit; print(urlsplit("$(ENV)").netloc)' \
)

week ?= $(shell date +"%V")

wordle	?= 505
score	?= 4/6

headers := -H "Content-type: application/json" \
	-H "X-Telegram-Bot-Api-Secret-Token: $(SECRET_KEY)"

msg := $(shell echo {} | jq '{ \
	message: { \
		chat: {id: $(CHAT_ID)}, \
		from: {id: 123, first_name: "test"}\
	} \
}')

reg := $(shell echo {} | jq '{ \
	url: "https://$(domain)", \
	secret_token: "$(SECRET_KEY)" \
}')

run:
	php \
		-d variables_order=EGPCS \
		-S $(domain) -t public

run-httpd:
	httpd -f $(PWD)/server/mac_dev.conf -DFOREGROUND \
		-C 'ServerName $(domain)' \
		-C 'Define SECRET_KEY $(SECRET_KEY)' \
		-C 'Define PWD $(PWD)'

test-wordle:
	@curl $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="Wordle $(wordle) $(score)"')' \
		$(ENV)

test-medals:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/medaljer $(week)"')' \
		$(ENV) | jq .text -r

test-golf:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/golf $(week)"')' \
		$(ENV) | jq .text -r

test-streaks:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/streaks"')' \
		$(ENV) | jq .text -r

test-reports:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/tabeller"')' \
		$(ENV) | jq .text -r

test-all: test-medals test-golf test-streaks test-reports

register-bot:
	@curl -l -H 'Content-type: application/json' \
		-d '$(shell echo '$(reg)' | jq -rc '.url="https://$(domain)/bot.php"')' \
		https://api.telegram.org/bot$(BOT_TOKEN)/setWebhook

deregister-bot:
	@curl -l -H 'Content-type: application/json' \
		-d '$(shell echo '$(reg)' | jq -rc '.url=""')' \
		https://api.telegram.org/bot$(BOT_TOKEN)/setWebhook

deploy:
	@rsync -rz \
		--exclude=".[!.]*" \
		--rsync-path="sudo -u www-data rsync" \
		public src $(SSH_HOST):/var/www/owordle/

deploy-config:
	@rsync \
		--rsync-path="sudo rsync" \
		server/apache.conf $(SSH_HOST):/etc/apache2/sites-available/owordle.conf
	@ssh $(SSH_HOST) sudo systemctl reload apache2
