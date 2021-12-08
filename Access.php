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

		} else {

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

		global $oauth_config;

		// If the access token has been removed from the session somehow, but the user is still logged in.
		// Could throw an exception
		// Could have a function somewhere that requests a new token and adds it to the session

		
		$api = new RestApiRequest($instanceUrl, $accessToken);

		$booksOnlineProducts = "'" . implode("','", $productIds) . "'";

		$today = new \DateTime();
		$today = $today->format("Y-m-d");

		// We need to make sure that the order is not in a draft status.  Order that is in "draft" stage should not give people access.

		//$query = "SELECT Id FROM OrderItem WHERE Contact__c = '$contactId' AND RealExpirationDate__c > $today AND Product2id IN($soqlProdIds)";
		$query = "SELECT Id, OrderId, Order.ActivatedDate, Order.EffectiveDate, RealExpirationDate__c FROM OrderItem WHERE Contact__c = '$contactId' AND Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True) AND RealExpirationDate__c > $today";


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