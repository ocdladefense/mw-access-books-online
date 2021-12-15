# mw-access-books-online (A MediaWiki extension, for determining/providing "Books Online" access) Version-1 

### Testing Script

##### Protected URL Examples
- https://lodtest.ocdla.org/DUII_Notebook:Chapter_1 
- https://lodtest.ocdla.org/Investigators_Manual:Chapter_1_Licensing
- https://lodtest.ocdla.org/Mental_Health_Manual:Chapter_1_A_Systematic_Approach

##### Tests (Test each url under the following circumstances)
With current subscription (Set the order status to "active")
- Logged out of Salesforce and logged out of the app.
- Logged into Salesforce and logged into the app.
- Logged into Salesforce and logged into the app, when there is no access token in the session.

Without current subscription (Set the order status to "draft")
- Logged out of Salesforce and logged out of the app.
- Logged into Salesforce and logged into the app.
- Logged into Salesforce and logged into the app, when there is no access token in the session.
