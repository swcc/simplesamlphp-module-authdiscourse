Using the Discourse authentication source with SimpleSAMLphp
==========================================================

Remember to configure `authsources.php` with the following configuration items:

| key      | type     | Description                                                                             |
|----------|----------|-----------------------------------------------------------------------------------------|
| `secret` | `string` | The SSO secret defined in your Discourse configuration (`sso provider secrets` setting) |
| `url`    | `string` | The URL of your Discourse server                                                        |

## Testing authentication

On the SimpleSAMLphp frontpage, go to the *Authentication* tab, and use the link:

  * *Test configured authentication sources*

Then choose the *discourse* authentication source.

Expected behaviour would then be that you are sent to Discourse, and asked to login:

TODO: Insert a picture

You will then be authenticated in SimpleSAMLphp and see an attribute set similar to this:

TODO: Insert a picture

