# slim-mailchimp

Notes:
-----
Framework Used : Slim3
Need to login to use the application. Sample username is "mahamud" and password is "password". Stored in json file under the database 
folder. The login will genrate a token that is required to be used on subsequent requetss

Installation Instructions:
-------------------------

1. Need to setup a virtual Host to run the app.
2. Please look in to app/config/config.dev.php for required environmental variables that require to be configured
3. An encryption key and Mailchimp API key to be set in the host configuration or can be hard coded in the config file
4. Host domain also requires to be configured.



Sample cUrl request
-------------------
```
Login Request

curl --request POST \
  --url https://api.loyalty.com/api/v1/login/ \
  --header 'cache-control: no-cache' \
  --header 'content-type: application/json' \
  --data '{ "username":"mahamud", "password":"password" }'


Get List (Use latest token)

curl -X GET \
  https://api.loyalty.com/api/v1/mail/lists\
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'x-auth-token: 6BVvDNUbCmFD6JV5hPe535KKi5aHgNYZ5BWtumUGAn47MMWan2rEVU8tPcUyIjHvYVS4lV66~JnyT5AL2CKhDNxSFCIi7Y7y2ES~Q4NyV8S3CfV3heEYxd7kkYnF4lB6COp72Qpm80.YjdRweu26w9tcAcmdvLyQaqgszUPwAnT7waS.~iGFSBQCCmgIa5cXyr5A2Bm5h~9d8MLOD4CGhacZ4gT0J2C2ejKIcsLsRlXYmtT1xqMjaCjyiHVUO3zt'

  
Create List

curl -X POST \
  https://api.loyalty.com/api/v1/mail/list \
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'x-auth-token: JvJtJdFPovgbKSFevz4VxKwhmvj~9Szwh3wCGu3v5rd4u8JfRVWxodFxbUCEuRrwRBsyvGLnXvQR73yMzE4zOnL2z8y4J0.DKnYzNb.waMa5Mtmp3IKmHDFZFzGvumeN.UlJHLk9Iy2I05R7dE.Ewv4t4XNNZiX4qk~ftmwamkdFkFoOQSt1Hxah6nJhuDdfXfxg8TjCim91H.nRl.bzyNDl0jQpaJ8ddGKMS0kf0jkAIM9GnuxJa3mx4di9my9c' \
  -d '{"name":"new_list", "company":"Haylix", "address1":"Duke Street", "city":"Melbourne","state":"VIC", "zip":"3000", "country":"Australia","permission_reminder":"Bla Bla bLa","from_name":"Peter","from_email":"peter@yahoo.com", "subject":"hello world", "language":"Bengali", "email_type_option":true}'

  
 link member to list

  curl -X POST \
  https://api.loyalty.com/api/v1/mail/list/29f52dcfcd/member \
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'postman-token: 60b4ee80-e613-8649-4144-59b7c09db6da' \
  -H 'x-auth-token: JvJtJdFPovgbKSFevz4VxKwhmvj~9Szwh3wCGu3v5rd4u8JfRVWxodFxbUCEuRrwRBsyvGLnXvQR73yMzE4zOnL2z8y4J0.DKnYzNb.waMa5Mtmp3IKmHDFZFzGvumeN.UlJHLk9Iy2I05R7dE.Ewv4t4XNNZiX4qk~ftmwamkdFkFoOQSt1Hxah6nJhuDdfXfxg8TjCim91H.nRl.bzyNDl0jQpaJ8ddGKMS0kf0jkAIM9GnuxJa3mx4di9my9c' \
  -d '{"email_address":"mahamud@gmail.com", "status":"subscribed"}
  
  
  Remove Member
  
  curl -X DELETE \
  https://api.loyalty.com/api/v1/mail/list/29f52dcfcd/members/1715009e206a9a2f75678f8806bccf21 \
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'postman-token: 5c54d689-ddec-3cf9-59e3-4b20002efcc1' \
  -H 'x-auth-token: JvJtJdFPovgbKSFevz4VxKwhmvj~9Szwh3wCGu3v5rd4u8JfRVWxodFxbUCEuRrwRBsyvGLnXvQR73yMzE4zOnL2z8y4J0.DKnYzNb.waMa5Mtmp3IKmHDFZFzGvumeN.UlJHLk9Iy2I05R7dE.Ewv4t4XNNZiX4qk~ftmwamkdFkFoOQSt1Hxah6nJhuDdfXfxg8TjCim91H.nRl.bzyNDl0jQpaJ8ddGKMS0kf0jkAIM9GnuxJa3mx4di9my9c'
  
 ``` 
