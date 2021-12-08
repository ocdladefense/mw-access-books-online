<?php

namespace AccessBooksOnline;

use \Salesforce\RestApiRequest;



class Access {

	public static function onUserGetRights(\User $user, array &$aRights) {

		if(!self::isBooksOnlineNamespace()) return true;

		// Guest users should not be able to view online products.  If read is set to true for guest users, you cant override that.  $wgGroupPermissons might take presidence.
		if($user->isAnon()){

			$aRights = array_filter($aRights, function($right){
			
				return $right != "read";
			});
			
			return true;
		}
		
		// Otherwise, check to see if the user has purchased the Books Online product.
		if(self::hasAccess()){
			
			$aRights[] = "read";

		} 
		
		// logged in users that don't have read permisson get the "permission error" page.
		// Url = MediaWiki:permissionserrors
		// url = https://lodtest.ocdla.org/index.php?title=MediaWiki:Permissionserrorstext-withaction&action=edit
		// orginal message: You do not have permission to $2, for the following {{PLURAL:$1|reason|reasons}}:
		// new message: You do not have a current [https://ocdla.force.com/OcdlaProduct?id=01t0a000004OuZtAAK Books Online Subscription]

		else {

			$aRights = array_filter($aRights, function($right){
			
				return $right != "read";
			});
		}

		return true;
	}



	public static function hasAccess() {

		$contactId = $_SESSION["sf-contact-id"];

		global $wgBooksOnlineProductIds;

		return self::hasCurrentSubscription($contactId, $wgBooksOnlineProductIds);
	}



	private static function hasCurrentSubscription($contactId, $productIds) {

		$accessToken = $_SESSION["access-token"];
		$instanceUrl = $_SESSION["instance-url"];

		if(empty($accessToken) || empty($instanceUrl)) {

			throw new Exception("Access Token or Instance URL are null.");
		}

		global $oauth_config;

		// Reset the access token on the session (maybe do this somewhere else?) Just redirect to the "Special:OAuthEndpoint/login"
		// Set the redirect on the session to the requested page
		// set a Location header to "Special:OAuthEndpoint"


		// 1. get the global $oauth_config
		// 2. $oauth = OAuthRequest::newAccessTokenRequest($config, $this->oauthFlow);
        // 3. $resp = $oauth->authorize();
	    // 4. $_SESSION["access-token"] = $resp->getAccessToken();
	    // 5. $_SESSION["instance-url"] = $resp->getInstanceUrl();

		// If the access token has been removed from the session somehow, but the user is still logged in.
		// Could throw an exception
		// Could have a function somewhere that requests a new token and adds it to the session
		// Scnario 1: The lib-salesforce-rest-api is not installed. fix: require the library in the composer.json
		// 2. oauth extension is not installed. fix: Require ocdladefense/mw-oauth in composer.json and throw an exception if it is not installed and set to active
		//    question: is there a way to know if an extension is installed and active.
		//	next best thing: if access toke or instance url are null, throw an exception
		
		$api = new RestApiRequest($instanceUrl, $accessToken);

		$booksOnlineProducts = "'" . implode("','", $productIds) . "'";

		$today = new \DateTime();
		$today = $today->format("Y-m-d");

		$minPurchaseDate = new \DateTime();
		$minPurchaseDate->modify("-367 days");
		$minPurchaseDate = $minPurchaseDate->format("Y-m-d");


		// We need to make sure that the order is not in a draft status.  Order that is in "draft" stage should not give people access.

		// Subscription should last only a year, but we dont have a reliable way of determining expiration.
		//$query = "SELECT Id FROM OrderItem WHERE Contact__c = '$contactId' AND RealExpirationDate__c > $today AND Product2id IN($soqlProdIds)";
		$query = "SELECT Id, OrderId, Order.ActivatedDate, Order.EffectiveDate FROM OrderItem WHERE Contact__c = '$contactId' AND Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True) AND Order.StatusCode != 'Draft' AND Order.EffectiveDate > $minPurchaseDate";


		$resp = $api->query($query);

		if(!$resp->success()) throw new \Exception($resp->getErrorMessage());

		$orderItemIds = array();
		foreach($resp->getRecords() as $record) {

			$orderItemIds[] = $record["Id"];
		}

		return !empty($orderItemIds);
	}


	public static function isBooksOnlineNamespace() {

		global $wgOcdlaBooksOnlineNamespaces, $wgTitle;

		return in_array($wgTitle->mNamespace, $wgOcdlaBooksOnlineNamespaces);
	}
}