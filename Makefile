env := http://localhost:8000/bot.php

week ?= $(shell date +"%V")

wordle	?= 505
score	?= 4/6

domain := owordle.dy.fi

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
		-S 0.0.0.0:8000 -t public

run-httpd:
	httpd -f $(PWD)/server/mac_dev.conf -DFOREGROUND \
		-C 'ServerName localhost:8000' \
		-C 'Define SECRET_KEY $(SECRET_KEY)' \
		-C 'Define PWD $(PWD)'

test-wordle:
	@curl -v $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="Wordle $(wordle) $(score)"')' \
		$(env)

test-medals:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/medaljer $(week)"')' \
		$(env) | jq .text -r

test-golf:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/golf $(week)"')' \
		$(env) | jq .text -r

test-streaks:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/streaks"')' \
		$(env) | jq .text -r

test-reports:
	@curl -s $(headers) \
		-d '$(shell echo '$(msg)' | jq -rc '.message.text="/tabeller"')' \
		$(env) | jq .text -r

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
		public src server $(SSH_HOST):/var/www/owordle/
