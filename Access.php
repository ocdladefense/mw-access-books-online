<?php

namespace AccessBooksOnline;

use \Salesforce\RestApiRequest;

// Url = MediaWiki:permissionserrors
// url = https://lodtest.ocdla.org/index.php?title=MediaWiki:Permissionserrorstext-withaction&action=edit
// orginal message: You do not have permission to $2, for the following {{PLURAL:$1|reason|reasons}}:
// new message: You do not have a current [https://ocdla.force.com/OcdlaProduct?id=01t0a000004OuZtAAK Books Online Subscription]


class Access {


	// Approach: after UserGetRights runs, we can still tell that the user doesn't have READ rights on the page.
	// If the user doesn't have read rights on the page, update the page's status code "onBeforePageDisplay."



	public static function onUserGetRights(\User $user, array &$aRights) {

		// Ignore non BON namespaces.
		if(!self::isBooksOnlineNamespace()) return true;

		// Retreive the user's Salesforce contactId, if assigned.
		$contactId = $_SESSION["sf-contact-id"];

		// By default, assume the user does not have BON access.
		$hasAccess = false;

		// Guest users should not be able to view online products.  If read is set to true for guest users, you cant override that.  $wgGroupPermissons might take presidence.
		if($user->isAnon() || false === $hasAccess = self::hasAccess($contactId)){

			$aRights = array_filter($aRights, function($right){
			
				return $right != "read";
			});
			
			http_response_code(403);
		}
		
		// Otherwise, check to see if the user has purchased the Books Online product
		else if($hasAccess) {
		
			$aRights[] = "read";
		} 


		return true;
	}



	public static function hasAccess($contactId) {

		global $wgBooksOnlineProductIds;

		return empty($contactId) ? false : self::hasCurrentSubscription($contactId, $wgBooksOnlineProductIds);
	}



	private static function hasCurrentSubscription($contactId, $productIds) {

		$accessToken = $_SESSION["access-token"];
		$instanceUrl = $_SESSION["instance-url"];

		// If the access token has been removed from the session, return false...for now.  (Need a better solution)
		if(empty($accessToken) || empty($instanceUrl)) return false;
		
		$api = new RestApiRequest($instanceUrl, $accessToken);

		$booksOnlineProducts = "'" . implode("','", $productIds) . "'";

		$minPurchaseDate = new \DateTime();
		$minPurchaseDate->modify("-367 days");
		$minPurchaseDate = $minPurchaseDate->format("Y-m-d");


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