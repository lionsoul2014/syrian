<?php
/**
 * link list util class:
 *
 * @author	chenxin<chenxin619315@gmail.com>
*/

//--------------------------------------------------------
class LinkedList
{
	/*
	 *size of the table
	*/
	private	$size;

	/*
	 * the head entry of the table
	*/
	public	$head;

	/*
	 * the tail entry of the table
	*/
	public	$tail;


	/*
	 * default contruct method
	 */
	public function __construct()
	{
		$this->size	= 0;
		$this->head = $this->_newEntry('#head#', NULL, NULL);
		$this->tail	= $this->_newEntry('#prev#', $this->head, NULL);
		$this->head->next = $this->tail;
	}

	/**
	 * interface to create new LinkedListEntry
	 *
	 * @param	value
	 * @param	prev
	 * @param	next
	 * @reeturn	Stdclass
	*/
	private function _newEntry($val, $prev, $next)
	{
		$cls		= new StdClass();

		$cls->data	= $val;
		$cls->prev	= $prev;
		$cls->next	= $next;

		return $cls;
	}

	/*
	 * get the size of the current table
	*/
	public function size()
	{
		return $this->size;
	}

	/*
	 * check if the table is empty
	*/
	public function isEmpty()
	{
		return ($this->size==0);
	}

	/**
	 * insert a new entry before the entry at the specifield position
	 *
	 * @param	idx
	 * @param	val
	 * @return	the Entry just added or false for failed
	*/
	public function add($idx, $val)
	{
		$entry	= $this->getEntry($idx);
		if ( $entry == NULL ) return false;

		return $this->insertBefore($entry, $val);
	}

	/**
	 * insert a new Entry at the head
	 *
	 * @param	val
	*/
	public function addFirst( $val )
	{
		return $this->insertBefore($this->head->next, $val);
	}

	/**
	 * append a new Entry from the tail
	 *
	 * @param	val
	*/
	public function addLast( $val )
	{
		return $this->insertBefore($this->tail, $val);
	}

	/**
	 * get the entry at the specifield position
	 *
	 * @param	idx
	 * @param	data of the Entry or false
	*/
	public function get( $idx )
	{
		$E	= $this->getEntry($idx);
		if  ( $E == NULL ) return false;

		return $E->data;
	}

	/**
	 * remove the entry at the specifield position
	 *
	 * @param	idx
	 * @return	data or false
	*/
	public function remove( $idx )
	{
		$entry	= $this->getEntry($idx);
		if ( $entry == NULL ) return false;

		return $this->removeEntry($entry);
	}

	/**
	 * remove the first entry of the collelction
	 *
	 * @return	the data of the removed entry or false
	*/
	public function removeFirst()
	{
		if ( $this->size == 0 ) return false;
		return $this->removeEntry($this->head->next);
	}

	/**
	 * remove the last entry of the collection
	 *
	 * @return	the data of the removed entry or false
	*/
	public function removeLast()
	{
		if ( $this->size == 0 ) return false;
		return $this->removeEntry($this->tail->prev);
	}

	/**
	 * remove the specifield Entry
	 *
	 * @param	Entry to remove
	 * @apram	the data of the removed entry or false
	*/
	public function removeEntry( $entry )
	{
		$entry->prev->next = $entry->next;
		$entry->next->prev = $entry->prev;

		$this->size--;
		$data	= $entry->data;
		unset($entry);

		return $data;
	}

	/**
	 * insert a new entry into the collection before the specifield position
	 *
	 * @param	Entry
	 * @param	val
	 * @return	the newly inserted Entry
	*/
	public function insertBefore( $entry, $val )
	{
		$E	= $this->_newEntry($val, $entry->prev, $entry);
		$entry->prev->next = $E;
		$entry->prev = $E;

		$this->size++;
		return $E;
	}

	/**
	 * get the Entry at the specifield position
	 *
	 * @param	idx for the entry
	 * @return	Entry
	*/
	public function getEntry($idx)
	{
		if ( $idx < 0 || $idx >= $this->size ) return NULL;

		$p	= NULL;

		if ( $idx < $this->size / 2 )
		{
			$p = $this->head;
			for ( $j = 0; $j < $idx; $j++ )
			{
				$p = $p->next;
			}
		}
		else
		{
			$p = $this->tail;
			for ( $j = $this->size; $j > $idx; $j-- )
			{
				$p = $p->prev;
			}
		}

		return $p;
	}

	/**
	 * get the iterator of the current Collection
	 *
	 * @return	the Iterator instance
	*/
	public function iterator()
	{
		return new LinkedListIterator($this);
	}
}

//linked list interator class
class LinkedListIterator
{
	/*
	 * current node
	*/
	private $current;

	/*
	 * ok to remove
	*/
	private $okToRemove;

	/*
	 * link pointer
	*/
	private $link;


	public function __construct( $link )
	{
		$this->current		= $link->head->next;
		$this->okToRemove	= false;
		$this->link			= $link;
	}

	/*
	 * is the current the last entnry?
	 * @Note: it may work strange under php 5.0
	*/
	public function hasNext()
	{
		return $this->current != $this->link->tail;
	}

	/*
	 * get the next entry
	*/
	public function next()
	{
		if ( ! $this->hasNext() ) return NULL;

		$data = &$this->current->data;
		$this->current = $this->current->next;
		$this->okToRemove = true;

		return $data;
	}

	/*
	 * remove the current entry
	*/
	public function remove()
	{
		if ( $this->okToRemove == false ) return false;

		$this->link->removeEntry($this->current->prev);
		$this->okToRemove = false;

		return true;
	}
}
?>
